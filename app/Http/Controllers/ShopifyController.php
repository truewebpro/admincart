<?php

namespace App\Http\Controllers;

use App\Models\ShopifyShop;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopifyController extends Controller
{
    public function getShopifyShop(Request $request)
    {
        $shopId = session('shop_id');
        $shopifyDetail = ShopifyShop::where('shop_id', $shopId)
            ->first();

        $service = new ShopifyService($shopifyDetail);
        $counts = $service->getImportCounts();

        return response()->json([
            'success' => true,
            'shopifyDetail' => $shopifyDetail ?? null,
            'counts' => $counts,
        ]);
    }

    public function addShopDetails(Request $request)
    {
        $shopId = session('shop_id');
        $existing = ShopifyShop::where('shop_id', $shopId)->first();
        $validatedData = $request->validate([
            'shop_domain' => [
                'required',
                'string',
                'regex:/^[a-z0-9\-]+\.myshopify\.com$/i',
                Rule::unique('shopify_shops', 'shop_domain')->ignore($existing?->id),
            ],
            'client_id' => ['required', 'string'],
            // required only when there's no existing row (first-time save)
            'client_secret' => [$existing ? 'nullable' : 'required', 'string'],

        ]);
        $shopifyShop = ShopifyShop::updateOrCreate(
            ['shop_id' => $shopId],
            [
                'shop_domain'   => $validatedData['shop_domain'],
                'client_id'     => $validatedData['client_id'],
                'client_secret' => $validatedData['client_secret'] ?? $existing?->client_secret,
            ]
        );

        try {
            app(ShopifyService::class,['shop' => $shopifyShop])->connect();
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'shopify' => 'Saved, but connecting to Shopify failed: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shopify store connected successfully.',
            'shopify_shop' => $shopifyShop,
        ]);
    }

    public function fetchToken()
    {
        $shopId = session('shop_id');
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();

        try {
            app(ShopifyService::class, ['shop' => $shopifyShop])->connect();
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'shopify' => 'Connecting to Shopify failed: ' . $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shopify token Generated successfully.',
            'token' => $shopifyShop->token,
        ]);
    }

    public function fetchProducts(Request $request)
    {
        $shopifyShop = ShopifyShop::where('shop_id','=',session('shop_id'))->first();
        $service = new ShopifyService($shopifyShop);
        $pros = $service->getProducts(25);
//        dd($pros);

//        $epros = [];
//        foreach ($pros as $pro) {
//            $npro['title'] = $pro['title'];
//            $npro['brand'] = $pro['vendor'];
//            $npro['publish_status'] = 'online';
//            $npro['body_html'] = $pro['body_html'];
//            $npro['handle'] = $pro['handle'];
//            $npro['product_type'] = $pro['product_type'];
//            $npro['status'] = $pro['status'];
//            $npro['tags'] = !empty($pro['tags'])
//                ? array_map('trim', explode(',', $pro['tags']))
//                : [];
//            $npro['thirdparty_id'] = $pro['id'];
//            $npro['image'] = $pro['image']['src'] ?? null;
//
//            $nvariants = [];
//            foreach ($pro['variants'] as $variant) {
//                $nops = [];
//                $nvals = null;
//                foreach ($pro['options'] as $noption) {
//                    $nops[] = $noption['name'];
////                    $nvals = [$noption['values'][0],$noption[1]];
////                    foreach ($noption['values'] as $noptionValue) {
////                        $nvals = [$noptionValue[0],$noptionValue[1]];
////                    }
//                }
//                $nvars['price'] = $variant['price'];
//                $nvars['barcode'] = $variant['barcode'];
//                $nvars['compareprice'] = $variant['compare_at_price'];
//                $nvars['sku'] = $variant['sku'];
//                $nvars['qty'] = $variant['inventory_quantity'];
//                $nvars['variant_image'] = $variant['image_id'];
//                $nvars['options'] = $nops;
//                $nvars['option_values'] = $nvals;
//                $nvariants[] = $nvars;
//            }
//            $npro['variants'] = $nvars;
//            $epros[] = $npro;
//        }



        return response()->json([
            'success' => true,
            'epros' => $epros ?? null,
            'pros' => $pros,

        ]);
    }

}
