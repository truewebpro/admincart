<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailtrapAccount extends Model
{
    use HasFactory;
    protected $table = 'mailtrap_accounts';
    protected $fillable = [
        'account_name',
        'api_key',
        'enabled',
    ];

    protected $casts = [
//        'api_key' => 'encrypted',
        'enabled' => 'boolean',
    ];

    public function shopLists(): HasMany
    {
        return $this->hasMany(
            ShopMailtrapList::class
        );
    }
}
