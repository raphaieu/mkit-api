<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\InstagramSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncInstagramDataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Backoff in seconds between retries: 1 min, 5 min, 15 min */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly User $user,
    ) {}

    public function handle(InstagramSyncService $service): void
    {
        $service->syncForUser($this->user);
    }

    public function failed(Throwable $e): void
    {
        Log::error('SyncInstagramDataJob failed permanently', [
            'user_id' => $this->user->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
