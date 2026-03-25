<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'contact_email',
        'contact_whatsapp',
        'city',
        'theme',
        'niches',
        'badges',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'pinterest_url',
        'twitter_url',
    ];

    protected function casts(): array
    {
        return [
            'niches' => 'array',
            'badges' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
