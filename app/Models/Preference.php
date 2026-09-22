<?php

namespace App\Models;

use App\Models\Concerns\HasMediaFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory, HasMediaFiles;
    protected $primaryKey = 'preference_id';
    protected $fillable = [
        'home_title',
        'home_description',
        'home_image',
        'shop_logo',
        'social_links',
        'shop_id',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    protected $hidden = ['mediaFiles'];

    protected $appends = ['home_image_alt', 'shop_logo_alt'];

    public function getHomeImageAltAttribute(): ?string
    {
        $media = $this->mediaFiles->first(fn ($file) => $file->pivot->media_for === 'home_image');
        return $media?->alt_text;
    }

    public function getShopLogoAltAttribute(): ?string
    {
        $media = $this->mediaFiles->first(fn ($file) => $file->pivot->media_for === 'logo');
        return $media?->alt_text;
    }

}
