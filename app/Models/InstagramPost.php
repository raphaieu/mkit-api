<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_profile_id',
        'instagram_media_id',
        'media_type',
        'media_url',
        'thumbnail_url',
        'permalink',
        'caption',
        'like_count',
        'comments_count',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }

    public function instagramProfile(): BelongsTo
    {
        return $this->belongsTo(InstagramProfile::class);
    }
}
