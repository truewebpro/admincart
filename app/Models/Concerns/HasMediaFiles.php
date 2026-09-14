<?php

namespace App\Models\Concerns;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasMediaFiles
{
    public function mediaFiles(): MorphToMany
    {
        return $this->morphToMany(
            MediaFile::class,
            'attachable',
            'media_file_attachments'
        )->withPivot('media_for', 'sort_order')->orderBy('sort_order');
    }

    public function getImagesAttribute(): array
    {
        return $this->mediaFiles->map(fn ($file) => [
            'path'      => $file->path,
            'alt'       => $file->alt_text,
            'width'     => $file->width,
            'height'    => $file->height,
            'media_for' => $file->pivot->media_for,
        ])->all();
    }
}
