<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\ShopifyShop;
use App\Models\ShopUser;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class ShopifyController extends Controller
{
    public function getShopifyShop(Request $request)
    {
        $shopId = session('shop_id');
        $shopifyDetail = ShopifyShop::where('shop_id', $shopId)
            ->first();

        $service = new ShopifyService($shopifyDetail);
        $counts = $service->getImportCounts();
        $blogs_count = Blog::where('shop_id', $shopId)->count();
        return response()->json([
            'success' => true,
            'shopifyDetail' => $shopifyDetail ?? null,
            'counts' => $counts,
            'blogs_count' => $blogs_count ?? null,
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

    public function importArticles(Request $request)
    {
        $shopifyShop = ShopifyShop::where('shop_id','=',session('shop_id'))->first();
        $service = new ShopifyService($shopifyShop);
        $articles = $service->getArticles(100);

        $blogs = [];
        $shopUser = ShopUser::where('shop_id','=',session('shop_id'))->where('role','!=','superadmin')->first();
        $userId = $shopUser?->user_id ?? 1;
        $existingBlogs = Blog::where('shop_id', session('shop_id'))->pluck('blog_image', 'blog_slug');
        foreach ($articles as $article) {
            $bpath = $existingBlogs->get($article['handle']);
            if(!$bpath){
                $bImage = $article['image'] ?? null;
                if(! empty($bImage['src'])) {
                    $imageUrl = $bImage['src'];
                    $response = Http::timeout(30)->get($imageUrl);
                    if($response->successful()) {
                        $filename = 'blog_image_'.uniqid().'.png';
                        $img = Image::make($response->body())->resize(1200, 800, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $bpath = 'blogs/'.$filename;
                        Storage::disk('s3')->put($bpath,(string)$img->encode());
                    }
                }
            }

            $articleImage = $bpath;
            $tags = !empty($article['tags']) ? array_map('trim', explode(',', $article['tags'])) : null;
            $blog = Blog::updateOrCreate(
                [
                    'shop_id' => session('shop_id'),
                    'blog_slug' =>  $article['handle'],
                ],
                [
                    'blog_title' => $article['title'],
                    'blog_description' => $article['body_html'],
                    'blog_excerpt' => $article['summary_html'],
                    'blog_image' => $articleImage,
                    'btags' => $tags,
                    'blog_status' => 'active',
                    'meta_title' => $article['title'],
                    'meta_desc' => $article['title'],
                    'user_id' => $userId,
                    'shop_id' => session('shop_id'),
                ]
            );
            $blogs[] = $blog;
        }

        return response()->json([
            'success' => true,
            'shop_user' => $shopUser ?? null,
            'articles' => $articles,
            'blogs' => $blogs,
        ]);
    }

}
