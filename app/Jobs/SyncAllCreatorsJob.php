<?php

namespace App\Jobs;

use App\Models\InstagramProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncAllCreatorsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $profiles = InstagramProfile::with('user')
            ->whereNotNull('access_token')
            ->get();

        $dispatched = 0;

        foreach ($profiles as $profile) {
            if ($profile->user === null) {
                continue;
            }

            SyncInstagramDataJob::dispatch($profile->user)
                ->onQueue('default');

            $dispatched++;
        }

        Log::info('SyncAllCreatorsJob dispatched sync jobs', ['count' => $dispatched]);
    }
}
