<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'is_active', 'spend_amount', 'points_per_spend',
        'min_order_amount_to_earn', 'max_points_per_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'spend_amount' => 'decimal:2',
        'min_order_amount_to_earn' => 'decimal:2',
    ];

    /**
     * Points earned for a single line item, based on the variant's price.
     * e.g. spend_amount=1.00, points_per_spend=1, price=19.49, qty=1 => 19 points
     * (floored per-unit, then multiplied by quantity, so £19.49 x 2 = 38 points, not 38.98 -> 38).
     */
    public function calculateItemPoints(float $price, int $quantity = 1): int
    {
        if (! $this->is_active || $price <= 0 || $quantity <= 0) {
            return 0;
        }

        $pointsPerUnit = (int) ceil(($price / $this->spend_amount) * $this->points_per_spend);

        return max($pointsPerUnit, 0) * $quantity;
    }
}
