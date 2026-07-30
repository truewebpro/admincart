<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Ctag extends Model
{
    use HasFactory;

    protected $table = 'ctags';

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ctag $ctag) {
            if (empty($ctag->slug)) {
                $ctag->slug = Str::slug($ctag->name);
            }
        });
    }

    public function draftOrders(): BelongsToMany
    {
        return $this->belongsToMany(DraftOrder::class, 'draft_order_ctag', 'ctag_id', 'draft_order_id')
            ->using(DraftOrderCtag::class)
            ->withTimestamps();
    }


    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_ctag', 'ctag_id', 'customer_id', 'id', 'customer_id')
            ->using(CustomerCtag::class)
            ->withTimestamps();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}
