<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreatorProfileResource;
use App\Models\CreatorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->creatorProfile
            ?? $request->user()->creatorProfile()->create([]);

        return CreatorProfileResource::make($profile)->response();
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name'     => ['nullable', 'string', 'max:255'],
            'bio'              => ['nullable', 'string', 'max:1000'],
            'contact_email'    => ['nullable', 'email', 'max:255'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'city'             => ['nullable', 'string', 'max:100'],
            'theme'            => ['nullable', 'in:gold,rose,ocean,sage'],
            'niches'           => ['nullable', 'array'],
            'niches.*'         => ['string', 'max:50'],
            'badges'           => ['nullable', 'array'],
            'badges.*'         => ['string', 'max:50'],
            'instagram_url'    => ['nullable', 'url', 'max:500'],
            'tiktok_url'       => ['nullable', 'url', 'max:500'],
            'youtube_url'      => ['nullable', 'url', 'max:500'],
            'pinterest_url'    => ['nullable', 'url', 'max:500'],
            'twitter_url'      => ['nullable', 'url', 'max:500'],
        ]);

        $profile = $request->user()->creatorProfile
            ?? $request->user()->creatorProfile()->create([]);

        $profile->update($data);

        return CreatorProfileResource::make($profile)->response();
    }
}
