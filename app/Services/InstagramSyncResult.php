<?php

namespace App\Services;

readonly class InstagramSyncResult
{
    public function __construct(
        public string $username,
        public int    $followersCount,
        public int    $mediaCount,
        public int    $postsUpserted,
        public bool   $insightsSynced = false,
    ) {}
}
