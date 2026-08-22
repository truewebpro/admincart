<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Stock;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function allInventory(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $type = $request->type;
        $brand = $request->brand;
        $tag = $request->tag;
        $status = $request->status;

        $query = Variant::Join('products','products.product_id','=','variants.product_id')
            ->join('stocks','stocks.variant_id','=','variants.variant_id')
            ->Join('product_types', 'product_types.product_type_id', '=', 'products.product_type_id')
            ->Join('brands', 'brands.brand_id', '=', 'products.brand_id')
            ->leftJoin(DB::raw("
            (
                SELECT variant_id,
                       SUM(allocated_quantity - shipped_quantity) as committed,
                       SUM(backorder_quantity) as backorders
                FROM order_items
                GROUP BY variant_id
            ) as oi
            "),'oi.variant_id','=','variants.variant_id')
            ->select('variants.variant_id','variants.sku','variants.variant_id','variants.variant_image','variants.option_values',
                'products.title','products.handle','products.product_status','products.featured_image','products.tags',
                'stocks.quantity','product_types.product_type_name','brands.brand_name',
                DB::raw('COALESCE(oi.backorders,0) as backorder_qty'),
                DB::raw('COALESCE(oi.committed,0) as committed'),
                DB::raw('(stocks.quantity - COALESCE(oi.committed,0)) as available'),
                'stocks.stock_id','stocks.location_id','stocks.product_id','stocks.shop_id')
            ->where('variants.shop_id','=',$shopId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.title', 'LIKE', "%{$search}%")
                    ->orWhere('variants.sku', 'LIKE', "%{$search}%")
                    ->orWhere('brands.brand_name', 'LIKE', "%{$search}%")
                    ->orWhere('product_types.product_type_name', 'LIKE', "%{$search}%");
            });
        }
        $allowedSorts = [
            'title' => 'products.title',
            'sku' => 'variants.sku',
            'quantity' => 'stocks.quantity',
            'committed' => 'committed',
            'backorder_qty' => 'backorder_qty',
            'product_status' => 'products.product_status',
        ];
        if ($type) {
            $query->where('product_types.product_type_name', $type);
        }
        if ($brand) {
            $query->where('brands.brand_name', $brand);
        }
        if ($tag) {
            $query->whereJsonContains('products.tags', $tag);
        }
        $this->applyProductStatusFilter($query, $status);
        $filters = [
            'brands' => Brand::where('shop_id', $shopId)
                ->orderBy('brand_name')
                ->pluck('brand_name'),

            'types' => ProductType::where('shop_id', $shopId)
                ->orderBy('product_type_name')
                ->pluck('product_type_name'),

            'tags' => Product::where('shop_id', $shopId)
                ->whereNotNull('tags')
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values(),
        ];

        $sortBy = $allowedSorts[$request->sort_by] ?? 'variants.variant_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = $query->count();
        }
        $perPage = $perPage > 0 ? $perPage : 50;
        $perPage = min($perPage, 500);
        $variants = $query->paginate($perPage);

        return response()->json([
            'variants' => $variants,
            'filters' => $filters,
        ],200);
    }

    private function applyProductStatusFilter($query, $status)
    {
        if (!$status || $status === 'All') {
            return;
        }

        if ($status === 'Archived') {
            $query->whereNotNull('products.deleted_at');
        } else {
            $query->whereNull('products.deleted_at')
                ->where('products.product_status', $status);
        }
    }

    public function updateInventory(Request $request)
    {
        $stock = Stock::Find($request['stock_id']);
        if($stock){
            if($request->action === 'set'){
                $stock->quantity = $request['set_qty'];
            } else {
                $stock->quantity += (int)$request->adjust_qty;
            }
            $stock->save();
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'stock' => $stock,
                'requests' => $request->all(),
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Stock Not Updated'
            ]);
        }
    }
}
