<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = [
        'shop_id',
        'thirdparty_id',
        'thirdparty_url',
        'file_type',
        'filename',
        'path',
        'alt_text',
        'mime_type',
        'width',
        'height',
        'file_size',
    ];
}
