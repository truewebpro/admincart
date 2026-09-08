<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLabel;

class ProductLabelService
{
    public function syncProduct(Product $product): void
    {
        $labels = ProductLabel::with('rules')
            ->whereHas('rules')
            ->where('shop_id', $product->shop_id)
            ->where('is_active', true)
            ->get();

        foreach ($labels as $label) {
            $rules = $label->rules->toArray() ?? [];
            $joinType = $rules[0]['join_type'] ?? 'and';
            $matches = $this->matchesRules($product, $rules, $joinType);

            if ($matches) {
                $label->products()->syncWithoutDetaching([
                    $product->product_id => ['shop_id' => $product->shop_id],
                ]);
            } else {
                $label->products()->detach($product->product_id);
            }
        }
    }

    private function matchesRules(Product $product, array $rules, string $joinType = 'and'): bool
    {
        $results = [];
        foreach ($rules as $rule) {
            switch ($rule['column']) {
                case 'tag':
                    $results[] = in_array($rule['condition'], $product->tags ?? []);
                    break;

                case 'type':
                    $results[] = $rule['relation'] === 'equals'
                        ? $product->product_type_id == $rule['condition']
                        : $product->product_type_id != $rule['condition'];
                    break;

                case 'brand':
                    $results[] = $rule['relation'] === 'equals'
                        ? $product->brand_id == $rule['condition']
                        : $product->brand_id != $rule['condition'];
                    break;

                case 'title':
                    $results[] = $rule['relation'] === 'contains'
                        ? str_contains(strtolower($product->title), strtolower($rule['condition']))
                        : !str_contains(strtolower($product->title), strtolower($rule['condition']));
                    break;
            }
        }
        return $joinType === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);
    }
}
