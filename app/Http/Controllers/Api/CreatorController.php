<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreatorResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CreatorController extends Controller
{
    private const EXPERIENCE_LIMIT = ['free' => 3, 'pro' => 20];

    public function show(string $handle): JsonResponse
    {
        $handle = ltrim($handle, '@');

        $user = User::where('handle', $handle)
            ->with([
                'creatorProfile',
                'instagramProfile.posts' => fn ($q) => $q
                    ->orderByDesc('timestamp')
                    ->limit(12),
                'instagramProfile.latestInsight',
                'partnerBrands' => fn ($q) => $q->orderBy('order'),
                'creatorLinks'  => fn ($q) => $q->where('active', true)->orderBy('order'),
            ])
            ->firstOrFail();

        $experienceLimit = self::EXPERIENCE_LIMIT[$user->plan] ?? 3;

        $user->setRelation(
            'portfolioPosts',
            $user->portfolioPosts()
                ->orderBy('order')
                ->limit($experienceLimit)
                ->get()
        );

        return CreatorResource::make($user)->response();
    }
}
