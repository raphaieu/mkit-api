<?php

namespace App\Actions\Creator;

use App\Models\InstagramProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class CreateCreatorAction
{
    /**
     * Find or create a User + InstagramProfile from a Socialite callback user.
     *
     * Provider field mapping (socialiteproviders/instagram):
     *   getId()       → instagram user id
     *   getName()     → username  (the provider maps `username` field → `name`)
     *   getNickname() → null      (not mapped by this provider)
     *   getAvatar()   → profile_picture_url
     *   getEmail()    → null      (not available via instagram_business_basic)
     *
     * The access_token received here is already the long-lived token —
     * the caller (InstagramController) is responsible for exchanging it first.
     */
    public function execute(SocialiteUser $socialiteUser, string $longLivedToken, \DateTimeInterface $tokenExpiresAt): User
    {
        return DB::transaction(function () use ($socialiteUser, $longLivedToken, $tokenExpiresAt): User {
            $profile = InstagramProfile::where('instagram_id', $socialiteUser->getId())->first();

            if ($profile !== null) {
                $profile->update([
                    'username'            => $socialiteUser->getName(),
                    'profile_picture_url' => $socialiteUser->getAvatar(),
                    'access_token'        => encrypt($longLivedToken),
                    'token_expires_at'    => $tokenExpiresAt,
                ]);

                return $profile->user;
            }

            $username = $socialiteUser->getName();

            $user = User::create([
                'name'   => $username,
                'handle' => $this->generateHandle($username),
            ]);

            InstagramProfile::create([
                'user_id'             => $user->id,
                'instagram_id'        => $socialiteUser->getId(),
                'username'            => $username,
                'profile_picture_url' => $socialiteUser->getAvatar(),
                'access_token'        => encrypt($longLivedToken),
                'token_expires_at'    => $tokenExpiresAt,
            ]);

            return $user;
        });
    }

    private function generateHandle(string $username): string
    {
        $base   = Str::slug($username, '_');
        $handle = $base;
        $suffix = 2;

        while (User::where('handle', $handle)->exists()) {
            $handle = "{$base}_{$suffix}";
            $suffix++;
        }

        return $handle;
    }
}
