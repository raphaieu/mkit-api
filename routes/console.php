<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync all creators' Instagram data daily
// Schedule::command('instagram:sync-all')->daily();
// TODO: implement SyncAllCreatorsJob and uncomment
