<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendcloudShippingOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'sendcloud_id',
        'shipping_option_code',
        'shipping_method_id',
        'carrier',
        'name',
        'functionalities',
        'is_active',
        'is_enabled',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function sendcloud():BelongsTo
    {
        return $this->belongsTo(Sendcloud::class);
    }

}
