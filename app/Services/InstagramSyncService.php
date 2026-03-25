<?php

namespace App\Services;

use App\Actions\Instagram\FetchInstagramInsightsAction;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InstagramSyncService
{
    private const API_BASE = 'https://graph.instagram.com/v23.0';

    private const PROFILE_FIELDS = 'id,username,name,biography,profile_picture_url,followers_count,follows_count,media_count,website';

    private const MEDIA_FIELDS = 'id,media_type,media_url,thumbnail_url,permalink,caption,like_count,comments_count,timestamp';

    private const MEDIA_LIMIT = 12;

    /**
     * Sync profile + recent posts for a user.
     *
     * Token resolution order:
     *   1. INSTAGRAM_TEST_TOKEN env (when set — for manual testing)
     *   2. User's stored long-lived token (production)
     *
     * @throws RuntimeException  when the user has no connected Instagram profile
     *                           and INSTAGRAM_TEST_TOKEN is also absent.
     */
    public function syncForUser(User $user): InstagramSyncResult
    {
        $token   = $this->resolveToken($user);
        $profile = $this->ensureProfile($user, $token);

        $profileData = $this->fetchProfile($token);
        $this->persistProfile($profile, $profileData);

        $mediaItems   = $this->fetchMedia($token);
        $postsUpserted = $this->persistMedia($profile, $mediaItems);

        $insightsSynced = $this->syncInsights($profile, $token);

        $profile->update(['last_synced_at' => now()]);

        Log::info('Instagram sync completed', [
            'user_id'         => $user->id,
            'username'        => $profileData['username'],
            'posts_upserted'  => $postsUpserted,
            'insights_synced' => $insightsSynced,
        ]);

        return new InstagramSyncResult(
            username:       $profileData['username'],
            followersCount: (int) ($profileData['followers_count'] ?? 0),
            mediaCount:     (int) ($profileData['media_count'] ?? 0),
            postsUpserted:  $postsUpserted,
            insightsSynced: $insightsSynced,
        );
    }

    // -------------------------------------------------------------------------
    // Token
    // -------------------------------------------------------------------------

    private function resolveToken(User $user): string
    {
        $testToken = config('services.instagram.test_token');

        if (filled($testToken)) {
            return $testToken;
        }

        $profile = $user->instagramProfile;

        if ($profile === null) {
            throw new RuntimeException(
                "User {$user->id} has no connected Instagram profile and INSTAGRAM_TEST_TOKEN is not set."
            );
        }

        return decrypt($profile->access_token);
    }

    // -------------------------------------------------------------------------
    // Bootstrap profile row on first test-token run
    // -------------------------------------------------------------------------

    /**
     * When using a test token, the user may not yet have an InstagramProfile.
     * We fetch /me to get the instagram_id and create a bare profile row so
     * subsequent operations have a valid FK to work with.
     */
    private function ensureProfile(User $user, string $token): InstagramProfile
    {
        if ($user->instagramProfile !== null) {
            return $user->instagramProfile;
        }

        // First time with a test token — bootstrap from the API.
        $data = $this->fetchProfile($token);

        $profile = InstagramProfile::create([
            'user_id'             => $user->id,
            'instagram_id'        => $data['id'],
            'username'            => $data['username'],
            'full_name'           => $data['name'] ?? null,
            'biography'           => $data['biography'] ?? null,
            'profile_picture_url' => $data['profile_picture_url'] ?? null,
            'followers_count'     => $data['followers_count'] ?? 0,
            'following_count'     => $data['follows_count'] ?? 0,
            'media_count'         => $data['media_count'] ?? 0,
            'access_token'        => encrypt($token),
            'token_expires_at'    => null,
        ]);

        // Reload relationship so $user->instagramProfile is warm.
        $user->setRelation('instagramProfile', $profile);

        return $profile;
    }

    // -------------------------------------------------------------------------
    // API calls
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException|ConnectionException
     */
    private function fetchProfile(string $token): array
    {
        return Http::timeout(15)
            ->get(self::API_BASE . '/me', [
                'fields'       => self::PROFILE_FIELDS,
                'access_token' => $token,
            ])
            ->throw()
            ->json();
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws RequestException|ConnectionException
     */
    private function fetchMedia(string $token): array
    {
        $response = Http::timeout(15)
            ->get(self::API_BASE . '/me/media', [
                'fields'       => self::MEDIA_FIELDS,
                'limit'        => self::MEDIA_LIMIT,
                'access_token' => $token,
            ])
            ->throw()
            ->json();

        return $response['data'] ?? [];
    }

    // -------------------------------------------------------------------------
    // Persistence
    // -------------------------------------------------------------------------

    private function persistProfile(InstagramProfile $profile, array $data): void
    {
        $profile->update([
            'instagram_id'        => $data['id'],
            'username'            => $data['username'],
            'full_name'           => $data['name'] ?? $profile->full_name,
            'biography'           => $data['biography'] ?? null,
            'profile_picture_url' => $data['profile_picture_url'] ?? null,
            'followers_count'     => (int) ($data['followers_count'] ?? 0),
            'following_count'     => (int) ($data['follows_count'] ?? 0),
            'media_count'         => (int) ($data['media_count'] ?? 0),
        ]);
    }

    /**
     * Attempt to sync insights. Returns true on success, false when the
     * instagram_manage_insights permission is not yet available (Phase 2).
     */
    private function syncInsights(InstagramProfile $profile, string $token): bool
    {
        $insight = (new FetchInstagramInsightsAction())->execute($profile, $token);

        return $insight !== null;
    }

    private function persistMedia(InstagramProfile $profile, array $items): int
    {
        $upserted = 0;

        foreach ($items as $item) {
            InstagramPost::updateOrCreate(
                ['instagram_media_id' => $item['id']],
                [
                    'instagram_profile_id' => $profile->id,
                    'media_type'           => $item['media_type'],
                    'media_url'            => $item['media_url'] ?? null,
                    'thumbnail_url'        => $item['thumbnail_url'] ?? null,
                    'permalink'            => $item['permalink'] ?? null,
                    'caption'              => $item['caption'] ?? null,
                    'like_count'           => (int) ($item['like_count'] ?? 0),
                    'comments_count'       => (int) ($item['comments_count'] ?? 0),
                    'timestamp'            => isset($item['timestamp'])
                        ? Carbon::parse($item['timestamp'])
                        : null,
                ]
            );

            $upserted++;
        }

        return $upserted;
    }
}
