<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFileAttachment extends Model
{
    protected $fillable = [
        'media_file_id',
        'attachable_type',
        'attachable_id',
        'media_for',
        'sort_order',
    ];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
