<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstagramProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'username'             => $this->username,
            'full_name'            => $this->full_name,
            'biography'            => $this->biography,
            'profile_picture_url'  => $this->profile_picture_url,
            'followers_count'      => $this->followers_count,
            'following_count'      => $this->following_count,
            'media_count'          => $this->media_count,
            'last_synced_at'       => $this->last_synced_at?->toISOString(),
        ];
    }
}
