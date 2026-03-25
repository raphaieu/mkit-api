<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatorLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'emoji'       => $this->emoji,
            'title'       => $this->title,
            'description' => $this->description,
            'url'         => $this->url,
            'order'       => $this->order,
            'active'      => $this->active,
        ];
    }
}
