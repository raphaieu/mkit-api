<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'image_url'            => $this->image_url,
            'partner_name'         => $this->partner_name,
            'collaboration_type'   => $this->collaboration_type,
            'reach'                => $this->reach,
            'engagement_rate_text' => $this->engagement_rate_text,
            'deliverables'         => $this->deliverables,
            'published_at'         => $this->published_at?->toISOString(),
            'order'                => $this->order,
        ];
    }
}
