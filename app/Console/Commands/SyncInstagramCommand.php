<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InstagramSyncService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Throwable;

class SyncInstagramCommand extends Command
{
    protected $signature = 'instagram:sync {userId : ID of the user to sync}';

    protected $description = 'Sync Instagram profile and posts for a given user';

    public function handle(InstagramSyncService $service): int
    {
        $userId = (int) $this->argument('userId');

        $user = User::find($userId);

        if ($user === null) {
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        $testToken = config('services.instagram.test_token');

        if (filled($testToken)) {
            $this->warn('Using INSTAGRAM_TEST_TOKEN (test mode).');
        }

        $this->info("Syncing Instagram data for user #{$userId} ({$user->name})...");

        try {
            $result = $service->syncForUser($user);

            $this->table(
                ['Field', 'Value'],
                [
                    ['Username',        '@' . $result->username],
                    ['Followers',       number_format($result->followersCount)],
                    ['Total media',     $result->mediaCount],
                    ['Posts upserted',  $result->postsUpserted],
                ]
            );

            $this->info('Done.');

            return self::SUCCESS;
        } catch (RequestException $e) {
            $body = $e->response->json();

            $this->error('Instagram API error: ' . ($body['error']['message'] ?? $e->getMessage()));
            $this->line('Code: ' . ($body['error']['code'] ?? 'n/a'));

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
