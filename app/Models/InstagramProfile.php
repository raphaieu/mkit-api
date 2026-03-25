<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instagram_id',
        'username',
        'full_name',
        'biography',
        'profile_picture_url',
        'followers_count',
        'following_count',
        'media_count',
        'access_token',
        'token_expires_at',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(InstagramPost::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(InstagramInsight::class);
    }

    public function latestInsight(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InstagramInsight::class)->latestOfMany('synced_at');
    }
}
