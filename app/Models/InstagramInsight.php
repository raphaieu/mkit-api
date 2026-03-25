<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramInsight extends Model
{
    protected $fillable = [
        'instagram_profile_id',
        'synced_at',
        'accounts_engaged_28d',
        'total_interactions_28d',
        'reach_28d',
        'profile_views_28d',
        'follower_count_delta_28d',
        'reach_series',
        'accounts_engaged_series',
        'audience_gender_age',
        'audience_country',
        'audience_city',
    ];

    protected function casts(): array
    {
        return [
            'synced_at'               => 'datetime',
            'reach_series'            => 'array',
            'accounts_engaged_series' => 'array',
            'audience_gender_age'     => 'array',
            'audience_country'        => 'array',
            'audience_city'           => 'array',
        ];
    }

    public function instagramProfile(): BelongsTo
    {
        return $this->belongsTo(InstagramProfile::class);
    }
}
