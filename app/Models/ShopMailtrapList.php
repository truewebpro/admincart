<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopMailtrapList extends Model
{
    use HasFactory;
    protected $table = 'shop_mailtrap_lists';
    protected $primaryKey = 'id';
    protected $fillable = [
        'shop_id',
        'mailtrap_account_id',
        'list_id',
        'list_name',
        'enabled',
        'sync_customers',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sync_customers' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function mailtrapAccount():BelongsTo
    {
        return $this->belongsTo(
            MailtrapAccount::class
        );
    }

    public function shop():BelongsTo
    {
        return $this->belongsTo(
            Shop::class,
            'shop_id',
            'shop_id'
        );
    }
}
