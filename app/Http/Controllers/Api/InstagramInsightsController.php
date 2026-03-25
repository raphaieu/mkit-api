<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstagramInsightsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramInsightsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->instagramProfile;

        if ($profile === null) {
            return response()->json(['message' => 'No Instagram profile connected.'], 404);
        }

        $insight = $profile->latestInsight;

        if ($insight === null) {
            return response()->json(['message' => 'No insights available yet. Trigger a sync first.'], 404);
        }

        return (new InstagramInsightsResource($insight))->response();
    }
}
