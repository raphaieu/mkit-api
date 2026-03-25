<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncInstagramDataJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        SyncInstagramDataJob::dispatch($request->user());

        return response()->json(['message' => 'Sync queued.'], 202);
    }
}
