<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Location;
use App\Models\Poptions;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Stock;
use App\Models\Tag;
use App\Models\Variant;
use App\Services\SmartCategoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected $shopId;

    public function __construct($shopId)
    {
        $this->shopId = $shopId;
    }

    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy('handle');

        $location = Location::where('shop_id', '=', $this->shopId)->firstOrFail();

        foreach ($grouped as $handle => $items) {
            $first = $items->first();

            try {
                DB::beginTransaction();
                $product = new Product();
                $product->shop_id = $this->shopId;
                $baseSlug = Str::slug(strtolower($first['title']), '-');
                $slug = $baseSlug;
                $counter = 1;
                while (Product::where('shop_id', $this->shopId)->where('handle', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $product->title = $first['title'];
                $product->handle = $slug ?? $handle;
                $product->body_html = $first['body_html'] ?? null;
                $product->product_status = 'Active';
                $product->publish_status = 'online';
                $product->product_type_id = $this->getTypeId($first['type']);
                $product->brand_id = $this->getBrandId($first['brand']);
                $product->tags = json_decode($first['tags']) ?? null;
                $product->featured_image = $first['featured_image'] ?? null;
                $product->thirdparty_id = 'exp_' . $this->shopId . '_' . time();
                $product->meta_title = $first['meta_title'] ?? null;
                $product->meta_desc = $first['meta_desc'] ?? null;
                $product->save();
                $ptags = json_decode($first['tags']) ?? null;
                if(!empty($ptags)) {
                    foreach ($ptags as $ptag) {
                        Tag::updateOrCreate(
                            ['tag_name' => $ptag,'shop_id' => $this->shopId],
                            ['tag_name' => $ptag,'shop_id' => $this->shopId]
                        );
                    }
                }
                foreach ($items as $item) {
                    $variant = Variant::create([
                        'product_id' => $product->product_id,
                        'sku' => $item['sku'],
                        'price' => $item['price'],
                        'compareprice' => $item['compareprice'] ?? null,
                        'costprice' => $item['costprice'] ?? null,
                        'barcode' => $item['barcode'] ?? null,
                        'variant_image' => $item['variant_image'] ?? null,
                        'options' => json_decode($item['options']) ?? null,
                        'option_values' => json_decode($item['option_values']) ?? null,
                        'istax' => $item['istax'] ?? 1,
                        'isdefault' => $item['isdefault'] ?? 1,
                        'weight' => $item['weight'] ?? 0.5,
                        'shop_id' => $this->shopId,
                    ]);
                    $proptions = json_decode($item['options']) ?? null;
                    if(!empty($proptions)) {
                        foreach ($proptions as $proption) {
                            Poptions::updateOrCreate(
                                ['option_name' => $proption,'shop_id' => $this->shopId],
                                ['option_name' => $proption,'shop_id' => $this->shopId]
                            );
                        }
                    }
                    $stock = new Stock();
                    $stock->quantity = $item['stock'] ?? 0;
                    $stock->location_id = $location['location_id'];
                    $stock->variant_id = $variant->variant_id;
                    $stock->product_id = $product->product_id;
                    $stock->shop_id = $this->shopId;
                    $stock->save();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Product import failed for handle {$handle}: ".$e->getMessage());
                continue;
            }
        }
        return collect();
    }

    private function getBrandId($brandName)
    {
        if (!$brandName) return null;
        return Brand::firstOrCreate(
            [
                'brand_name' => trim($brandName),
                'brand_slug' => Str::slug(strtolower(trim($brandName)),'-'),
                'shop_id' => $this->shopId,
            ]
        )->brand_id;
    }

    private function getTypeId($typeName)
    {
        if (!$typeName) return null;
        return ProductType::firstOrCreate(
            [
                'product_type_name' => trim($typeName),
                'shop_id' => $this->shopId,
            ])->product_type_id;
    }

}
