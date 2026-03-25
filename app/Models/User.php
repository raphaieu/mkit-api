<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'handle',
        'plan',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function instagramProfile(): HasOne
    {
        return $this->hasOne(InstagramProfile::class);
    }

    public function creatorProfile(): HasOne
    {
        return $this->hasOne(CreatorProfile::class);
    }

    public function portfolioPosts(): HasMany
    {
        return $this->hasMany(PortfolioPost::class);
    }

    public function partnerBrands(): HasMany
    {
        return $this->hasMany(PartnerBrand::class);
    }

    public function creatorLinks(): HasMany
    {
        return $this->hasMany(CreatorLink::class);
    }
}
