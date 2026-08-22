<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnrichesWithLoyaltyPoints;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\Product;
use App\Models\Section;
use App\Models\Stype;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class BrandController extends Controller
{
    use EnrichesWithLoyaltyPoints;

    public function allBrands(Request $request)
    {
        $shopId = $request->shop_id;
        $brands = Cache::remember(
            CacheKeys::brands($shopId),
            now()->addHours(12),
            function() use ($shopId) {
                return Brand::query()->select(
                    'brand_id', 'brand_name', 'brand_slug', 'brand_image','brand_status','shop_id'
                )->withCount('product')->where('shop_id','=',$shopId)
                    ->where('brand_status','=','Active')
                    ->orderBy('brand_name','ASC')->get();
            });
        return response()->json([
            'status' => true,
            'brands' => $brands
        ],200);

    }

    public function getBrandBySlug(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $brand = Cache::remember(
            CacheKeys::brand($shopId, $brand_slug),
            now()->addHours(12),
            fn() => Brand::with('faqs')->where('shop_id', $shopId)
                ->where('brand_slug', $brand_slug)
                ->first()
        );
        if(!$brand){
            return response()->json([
                'status' => false,
                'brand' => null,
            ]);
        }
        $brand_products = Product::query()
            ->select(
                'product_id',
                'title',
                'handle',
                'featured_image',
                'product_status',
                'product_type_id',
                'brand_id',
                'tags'
            )
            ->with('brand','variants.astock')
            ->withCount('reviews')
            ->withAvg('reviews','rating')
            ->where('brand_id', $brand->brand_id)
            ->where('product_status', 'Active')
            ->paginate(12);

        $allVariants = $brand_products->getCollection()->flatMap(fn ($product) => $product->variants);
        $this->attachLoyaltyPointsToMany($shopId, $allVariants);
//        $brand_products = Cache::remember(
//            CacheKeys::brandProducts($shopId, $brand_slug),
//            now()->addHours(6),
//            function () use ($brand) {
//                return Product::query()
//                    ->select(
//                        'product_id',
//                        'title',
//                        'handle',
//                        'featured_image',
//                        'product_status',
//                        'product_type_id',
//                        'brand_id',
//                        'tags'
//                    )
//                    ->with('brand','variants.astock')
//                    ->withCount('reviews')
//                    ->withAvg('reviews','rating')
//                    ->where('brand_id', $brand->brand_id)
//                    ->where('product_status', 'Active')
//                    ->paginate(12);
//            }
//        );

        return response()->json([
            'status' => true,
            'brand' => $brand,
            'brand_products' => $brand_products ?? null,
        ]);
    }

    public function
    getBrandSections(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::brandSections($shopId, $brand_slug),
            now()->addHours(12),
            function() use ($shopId, $brand_slug) {
                $brand = Brand::with('bsections')
                    ->where('shop_id','=',$shopId)
                    ->where('brand_slug','=',$brand_slug)
                    ->first();
                if (!$brand) {
                    return null;
                }
                $sectionsWithExtras = [];
                foreach ($brand->bsections as $section){
                    $sectionArray = $section->toArray();
                    if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                        $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                        $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                        $products = Product::with(['variants.astock', 'brand', 'ptype'])
                            ->where('shop_id','=',$shopId)
                            ->whereIn('product_id', function ($query) use ($catId) {
                                $query->select('product_id')
                                    ->from('catpros')
                                    ->where('cat_id', $catId);
                            })
                            ->limit($sectionArray['section_json']['stype_json']['plimit'] ?? 12)
                            ->get();
                        $allVariants = $products->flatMap(fn ($product) => $product->variants);
                        $this->attachLoyaltyPointsToMany($shopId, $allVariants);
                        $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                        $sectionArray['section_json']['stype_json']['catpros'] = $products;
                    }
                    $sectionsWithExtras[] = $sectionArray;
                }
                return [
                    'sections' => $sectionsWithExtras,
                    'brand_slug' => $brand_slug,
                ];
            }
        );

        if (!$data) {
            return response()->json([
                'status' => false,
                'sections' => null,
                'brand_slug' => $brand_slug,
            ]);
        }

        return response()->json([
            'status' => true,
            'sections' => $data['sections'],
            'brand_slug' => $data['brand_slug'],
        ]);
    }

    public function prosByBrand(Request $request,$shopname,$brand_slug)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::brandPage($shopId, $brand_slug),
            now()->addHours(6),
            function() use ($shopId, $brand_slug) {
                $brand = Brand::where('shop_id','=',$shopId)
                    ->where('brand_slug','=',$brand_slug)
                    ->first();

                if($brand != null){
                    $brandsections = Section::where('sectionable_id','=',$brand->brand_id)
                        ->where('sectionable_type',Brand::class)
                        ->join('stypes','stypes.stype_id','=','sections.stype_id')
                        ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                            'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
                        ->where('sections.section_status','=','show')
                        ->orderBy('sections.sort_order', 'ASC')
                        ->get();
                    $sectionsWithExtras = [];
                    foreach ($brandsections as $section){
                        $sectionArray = $section->toArray();
                        if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                            $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                            $catSlug = Cat::where('cat_id','=',$catId)->first()->cat_slug;
                            $products = Product::with(['variants.astock', 'brand', 'ptype'])
                                ->where('shop_id','=',$shopId)
                                ->withCount('reviews')->withAvg('reviews','rating')
                                ->whereIn('product_id', function ($query) use ($catId) {
                                    $query->select('product_id')
                                        ->from('catpros')
                                        ->where('cat_id', $catId);
                                })
                                ->limit($sectionArray['section_json']['stype_json']['plimit'] ?? 12)
                                ->get();
                            $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                            $sectionArray['section_json']['stype_json']['catpros'] = $products;
                        }
                        $sectionsWithExtras[] = $sectionArray;
                    }
                    $brand->asections = $sectionsWithExtras;
                    $brand_products = Product::with('brand','variants.astock')
                        ->withCount('reviews')->withAvg('reviews','rating')
                        ->where('brand_id','=',$brand->brand_id)
                        ->where('product_status','=','Active')
                        ->inRandomOrder()
                        ->get();
                    $total_reviews = 0;
                    $review_averages = [];
                    foreach ($brand_products as $product){
                        $productArray = $product->toArray();
                        $reviewcount = $product->reviews_count;
                        if($reviewcount < 1){ $reviewcount = 2; }
                        $total_reviews += $reviewcount;
                        $average = $product->reviews_avg_rating ?? 5;
                        $review_averages[] = $average;
                    }
                    $brand['total_reviews'] = $total_reviews;
                    $brand['total_average'] = count($review_averages) > 0
                        ? array_sum($review_averages) / count($review_averages)
                        : 5;
                    return response()->json([
                        'status' => true,
                        'brand' => $brand,
                        'brand_products' => $brand_products,
                    ],200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Brand not found'
                    ],404);
                }
            }
        );
        return response()->json($data);
    }

    //admin Routes
    public function allAdminBrands(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $allowedSorts = [
            'brand_name' => 'brand_name',
            'brand_status' => 'brand_status',
            'products_count' => 'products_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $query = Brand::query()
            ->where('shop_id', $shopId)
            ->withCount(['products']);
        if ($search) {
            $terms = explode(' ', $search);
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where('brand_name', 'LIKE', "%{$term}%");
                }
            });
        }
        if ($status && $status !== 'All') {
            $query->where('brand_status', $status);
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'brand_id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $brands = $query->paginate($perPage);
        return response()->json([
            'brands' => $brands
        ],200);
    }

    public function brandById($brand_id)
    {
        $shopId = session('shop_id');
        $brand = Brand::with('sections','faqs')
            ->where('brand_id', $brand_id)
            ->first();
        if(!$brand){
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ]);
        }
        $brands = Brand::where('shop_id', $shopId)->select('brand_id','brand_name')->get();
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        $cats = Cat::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $pros = Product::where('shop_id','=',$shopId)
            ->select('products.product_id','products.featured_image','products.title','products.product_status')
            ->get();
        return response()->json([
            'status' => 200,
            'brand' => $brand,
            'brands' => $brands,
            'stypes' => $stypes,
            'cats' => $cats,
            'pros' => $pros,
            'message' => "Brand Detail",
        ]);
    }

    public function updateBrand(Request $request)
    {
        $shopId = session('shop_id');
        $existingBrand  = Brand::where('shop_id','=',$shopId)
            ->where('brand_id',$request['brand_id'])
            ->first();
        $bpath = $existingBrand?->brand_image ?? null;
        if($request->hasFile('brand_image')){
            if ($existingBrand && $existingBrand->brand_image) {
                Storage::disk('s3')->delete($existingBrand->brand_image);
            }
            $Image = $request->file('brand_image');
            $filename = 'brand_'.uniqid().'.png';
            $img = Image::make($Image->getRealPath())->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $bpath = 'images/brand/'.$filename;
            Storage::disk('s3')->put($bpath, (string) $img->encode());
        }
        $baseSlug = Str::slug($request['brand_slug'] ?? $request['brand_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Brand::where('shop_id','=',$shopId)
            ->where('brand_slug', '=', $slug)
            ->where('brand_id', '!=', $request['brand_id'])
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $request['brand_slug'] = $slug;
        $brand = Brand::updateOrCreate(
            [
                'shop_id' => $shopId,
                'brand_id' => $request['brand_id'],
            ],
            [
                'brand_name'=>$request['brand_name'],
                'brand_slug' => $request['brand_slug'],
                'brand_desc' => $request['brand_desc'],
                'brand_status' => $request['brand_status'] ?? "Active",
                'brand_image' => $bpath,
                'shop_id' => $shopId,
                'meta_title' => $request['meta_title'],
                'meta_desc' => $request['meta_desc'],
            ]
        );
        if($brand){
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully',
                'brand' => $brand,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Brand Not Updated'
            ],401);
        }
    }

    public function deleteBrand(Request $request)
    {
        $brand = Brand::findOrFail($request['brand_id']);
        $brand->delete();
        return response()->json(['success'=>true,'message' => "Brand Deleted Success"]);
    }
}
