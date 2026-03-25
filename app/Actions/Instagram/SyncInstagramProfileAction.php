<?php

namespace App\Actions\Instagram;

use App\Models\InstagramProfile;
use App\Models\User;
use App\Services\InstagramSyncService;

class SyncInstagramProfileAction
{
    public function __construct(
        private readonly InstagramSyncService $service,
    ) {}

    public function execute(User $user): InstagramProfile
    {
        $this->service->syncForUser($user);

        return $user->instagramProfile()->firstOrFail();
    }
}
