<?php
namespace App\Services;

use App\Models\ProductPriceTier;

class TierPricingService
{
    public function apply($cart)
    {
        foreach ($cart as &$item) {

            $tiers = ProductPriceTier::where('product_id', $item['product_id'])
                ->orderBy('min_qty', 'desc')
                ->get();

            foreach ($tiers as $tier) {

                if ($item['qty'] >= $tier->min_qty) {

                    if ($tier->pricing_type === 'fixed') {
                        $item['price'] = $tier->price;
                    } else {
                        // percentage discount
                        $item['price'] = $item['base_price'] - (
                                ($item['base_price'] * $tier->price) / 100
                            );
                    }

                    break;
                }
            }
        }

        return $cart;
    }
}
