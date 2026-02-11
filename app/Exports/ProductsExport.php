<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection,withHeadings,ShouldAutoSize
{
    protected $shopId;
    public function __construct($shopId){
        $this->shopId = $shopId;
    }
    public function collection():Collection
    {
        $products =  Product::with([
            'variants','brand','ptype'
        ])->where('shop_id', $this->shopId)->get();

        $rows = collect();

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $rows->push([
                    'Product ID'       => $product->product_id,
                    'Title'     => $product->title,
                    'Handle'            => $product->handle,
                    'Brand'            => $product->brand->brand_name,
                    'Type'             => $product->ptype->product_type_name,
                    'Tags'             => $product->tags,
                    'Variant ID'       => $variant->variant_id,
                    'SKU'              => $variant->sku,
                    'Price'            => $variant->price,
                    'Stock (by Loc)'   => $variant->stock,
                    'Options'          => $variant->options,
                    'Option_values'     => $variant->option_values,
                    'Featured_image'   => $product->featured_image,
                    'Variant_image'   => $variant->variant_image,
                ]);
            }
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Title',
            'Handle',
            'Brand',
            'Type',
            'Tags',
            'Variant ID',
            'SKU',
            'Price',
            'Stock',
            'Options',
            'Option_values',
            'Featured_image',
            'Variant_image',
        ];
    }
}
