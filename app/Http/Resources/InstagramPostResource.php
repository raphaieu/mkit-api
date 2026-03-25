<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstagramPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'instagram_media_id' => $this->instagram_media_id,
            'media_type'         => $this->media_type,
            'media_url'          => $this->media_url,
            'thumbnail_url'      => $this->thumbnail_url,
            'permalink'          => $this->permalink,
            'caption'            => $this->caption,
            'like_count'         => $this->like_count,
            'comments_count'     => $this->comments_count,
            'timestamp'          => $this->timestamp?->toISOString(),
        ];
    }
}
