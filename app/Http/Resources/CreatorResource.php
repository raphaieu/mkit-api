<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $instagram = $this->whenLoaded('instagramProfile');

        return [
            'name'          => $this->name,
            'handle'        => $this->handle,
            'plan'          => $this->plan,
            'profile'       => CreatorProfileResource::make($this->whenLoaded('creatorProfile')),
            'instagram'     => $instagram ? $this->instagramData($instagram) : null,
            'experiences'   => PortfolioPostResource::collection($this->whenLoaded('portfolioPosts')),
            'partner_brands' => PartnerBrandResource::collection($this->whenLoaded('partnerBrands')),
            'links'         => CreatorLinkResource::collection($this->whenLoaded('creatorLinks')),
            // Insights (Fase 2 — instagram_manage_insights)
            'insights'      => $this->insightsData($instagram),
        ];
    }

    private function instagramData(mixed $profile): array
    {
        $posts = $profile->relationLoaded('posts') ? $profile->posts : collect();

        return [
            'username'            => $profile->username,
            'full_name'           => $profile->full_name,
            'biography'           => $profile->biography,
            'profile_picture_url' => $profile->profile_picture_url,
            'followers_count'     => $profile->followers_count,
            'following_count'     => $profile->following_count,
            'media_count'         => $profile->media_count,
            'engagement_rate'     => $this->calcEngagementRate($profile, $posts),
            'last_synced_at'      => $profile->last_synced_at?->toISOString(),
            'posts'               => InstagramPostResource::collection($posts),
        ];
    }

    /**
     * Engagement rate = avg (likes + comments) per post / followers × 100
     */
    private function calcEngagementRate(mixed $profile, mixed $posts): ?float
    {
        if ($posts->isEmpty() || $profile->followers_count === 0) {
            return null;
        }

        $avgInteractions = $posts->avg(fn ($p) => $p->like_count + $p->comments_count);

        return round($avgInteractions / $profile->followers_count * 100, 2);
    }

    private function insightsData(mixed $instagram): ?array
    {
        if ($instagram === null) {
            return null;
        }

        $insight = $instagram->relationLoaded('latestInsight')
            ? $instagram->latestInsight
            : null;

        if ($insight === null) {
            return null;
        }

        return (new InstagramInsightsResource($insight))->resolve();
    }
}
