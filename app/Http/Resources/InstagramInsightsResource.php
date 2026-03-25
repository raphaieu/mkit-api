<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstagramInsightsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'synced_at' => $this->synced_at?->toISOString(),

            'performance' => [
                'reach_28d'                => $this->reach_28d,
                'accounts_engaged_28d'     => $this->accounts_engaged_28d,
                'total_interactions_28d'   => $this->total_interactions_28d,
                'profile_views_28d'        => $this->profile_views_28d,
                'follower_count_delta_28d' => $this->follower_count_delta_28d,
                'reach_series'             => $this->reach_series ?? [],
                'accounts_engaged_series'  => $this->accounts_engaged_series ?? [],
            ],

            'audience' => [
                'gender_age' => $this->audience_gender_age,
                'country'    => $this->audience_country,
                'city'       => $this->audience_city,
            ],
        ];
    }
}
