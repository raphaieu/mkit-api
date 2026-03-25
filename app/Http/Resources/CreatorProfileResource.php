<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'display_name'     => $this->display_name,
            'bio'              => $this->bio,
            'contact_email'    => $this->contact_email,
            'contact_whatsapp' => $this->contact_whatsapp,
            'city'             => $this->city,
            'theme'            => $this->theme,
            'niches'           => $this->niches ?? [],
            'badges'           => $this->badges ?? [],
            'social'           => [
                'instagram' => $this->instagram_url,
                'tiktok'    => $this->tiktok_url,
                'youtube'   => $this->youtube_url,
                'pinterest'  => $this->pinterest_url,
                'twitter'   => $this->twitter_url,
            ],
        ];
    }
}
