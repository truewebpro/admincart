<?php

namespace App\Services;

use App\Models\Cat;
use App\Models\Product;

class SmartCategoryService
{
    public function syncProduct(Product $product)
    {
        $cats = Cat::with('rules')->where('cat_type', '=', 'smart')->get();
        foreach ($cats as $cat) {
            $rules = $cat->rules->toArray() ?? [];
            $matches = $this->matchesRules($product, $rules, $cat->cat_rule ?? 'and');

            if ($matches) {
//                $cat->cpros()->syncWithoutDetaching([$product->product_id]);
                $cat->cpros()->syncWithoutDetaching([
                    $product->product_id => ['shop_id' => $product->shop_id],
                ]);
            } else {
                $cat->cpros()->detach($product->product_id);
            }
        }
    }

    private function matchesRules(Product $product, array $rules,string $joinType = 'and'):bool
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

                case 'vendor':
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
