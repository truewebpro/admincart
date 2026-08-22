<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnrichesWithLoyaltyPoints;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\Catpro;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\RelatedCat;
use App\Models\Stype;
use App\Models\Tag;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CatController extends Controller
{
    use EnrichesWithLoyaltyPoints;
    public function allCats(Request $request)
    {
        $shopId = $request->shop_id;
        $shopcats = Cache::remember(
            CacheKeys::cats($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $acats = Cat::where('shop_id','=',$shopId)
                    ->where('cat_status','=','Active')
                    ->get();
                foreach ($acats as $shopcat){
                    $productId = Catpro::where('cat_id', $shopcat->cat_id)->value('product_id');
                    $shopcat['proimage'] = $productId
                        ? Product::where(
                            'product_id',
                            $productId
                        )->value('featured_image')
                        : null;
                }
                return $acats;
            }
        );
            return response()->json([
                'status' => true,
                'cats' => $shopcats,
            ],200);

    }

    public function getAllCats(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $shopcats = Cache::remember(
            CacheKeys::cats($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                $acats = Cat::where('shop_id','=',$shopId)
                    ->where('cat_status','=','Active')
                    ->select('cat_id','cat_slug','cat_name','cat_status','cat_image','shop_id')
                    ->get();
                foreach ($acats as $acat){
                    $proimage = null;
                    $productId = Catpro::where('cat_id', '=', $acat->cat_id)->value('product_id');
                    $acat['proimage'] = $productId
                        ? Product::where(
                            'product_id',
                            $productId
                        )->value('featured_image')
                        : null;
                }
                return $acats;
            }
        );
        return response()->json([
            'status' => true,
            'cats' => $shopcats,
        ],200);

    }

    public function getCatBySlug(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat  = Cache::remember(
            CacheKeys::cat($shopId,$slug),
            now()->addHours(12),
            function() use ($shopId,$slug){
                return Cat::with('rcats','faqs')
                    ->where('shop_id','=',$shopId)
                    ->where('cat_slug','=',$slug)
                    ->first();
            }
        );
        if (!$cat) {
            return response()->json([
                'status' => false,
                'type' => null,
                'slug' => $slug,
                'cat' => null,
            ]);
        }

        $catId = $cat->cat_id;
        $alpros = Product::query()
            ->select(
                'products.product_id',
                'products.title',
                'products.handle',
                'products.featured_image',
                'products.product_status',
                'products.product_type_id',
                'products.brand_id',
                'products.tags')
            ->join('catpros', 'products.product_id', '=', 'catpros.product_id')
                ->where('catpros.cat_id', $catId)
                ->where('products.shop_id', $shopId)
            ->with(['variants.astock', 'brand', 'ptype'])
//            ->where('shop_id','=',$shopId)
            ->withCount('reviews')
            ->withAvg('reviews','rating')
//            ->whereIn('product_id', function ($query) use ($catId) {
//                $query->select('product_id')
//                    ->from('catpros')
//                    ->where('cat_id', $catId);
//            })
            ->orderBy('catpros.position','asc')
            ->paginate(24);
        $allVariants = $alpros->getCollection()->flatMap(fn ($product) => $product->variants);
        $this->attachLoyaltyPointsToMany($shopId, $allVariants);

        $cat['catpros'] = $alpros;
        return response()->json([
            'status' => true,
            'type' => 'Category',
            'slug' => $slug,
            'cat' => $cat,
        ]);
    }

    public function getCatSections(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat  = Cache::remember(
            CacheKeys::catSectionsData($shopId,$slug),
            now()->addHours(12),
            function() use ($shopId,$slug){
                return Cat::with('csections')
                    ->where('shop_id','=',$shopId)
                    ->where('cat_slug','=',$slug)
                    ->first();
            }
        );

        if (!$cat) {
            return response()->json([
                'status' => false,
                'sections' => null,
                'slug' => $slug,
            ]);
        }
        $sectionsWithExtras = Cache::remember(
            CacheKeys::catSections($shopId,$slug),
            now()->addHours(12),
            function () use ($shopId,$cat){
                $sectionsWithExtras = [];
                foreach ($cat->csections as $section){
                    $sectionArray = $section->toArray();
                    if ($sectionArray['section_json']['stype_slug'] === 'featured_products') {
                        $catId = $sectionArray['section_json']['stype_json']['cat_id'];
                        $catSlug = Cat::where('cat_id', $catId)->value('cat_slug');
                        $products = Product::query()
                            ->select('product_id','title','handle','featured_image','product_status','product_type_id',
                                'brand_id','tags')->with(['variants.astock', 'brand', 'ptype'])
                            ->where('shop_id','=',$shopId)
                            ->withCount('reviews')->withAvg('reviews','rating')
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
                return $sectionsWithExtras;
            }
        );

        return response()->json([
            'status' => true,
            'sections' => $sectionsWithExtras,
            'slug' => $slug,
        ]);
    }

    public function getCategory(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $cat = Cat::with('rcats','csections','faqs')
            ->where('shop_id','=',$shopId)
            ->where('cat_slug','=',$slug)
            ->first();
        if($cat != null){
            $catId = $cat->cat_id;
            $alpros = Product::with(['variants.astock', 'brand', 'ptype'])
                ->withCount('reviews')->withAvg('reviews','rating')
                ->whereIn('product_id', function ($query) use ($catId) {
                    $query->select('product_id')
                        ->from('catpros')
                        ->where('cat_id', $catId);
                })->inRandomOrder()
                ->get();
            $cat['catpros'] = $alpros;
            $sectionsWithExtras = [];
            foreach ($cat->csections as $section){
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
            $cat->asections = $sectionsWithExtras;
            return response()->json([
                'status' => true,
                'type' => "Category",
                'slug'=> $slug,
                'cat' => $cat,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'type' => null,
                'slug'=> $slug,
                'cat' => null,
            ]);
        }
    }

    //Admin Routes
    public function allAdminCats()
    {
        $shopId = session('shop_id');
        $cats = Cat::query()
            ->select('cat_id', 'cat_name','cat_image','cat_slug','cat_status','cat_type','shop_id','updated_at')
            ->with('rules')->withCount('catpros')
            ->where('shop_id','=',$shopId)
            ->orderByDesc('updated_at')
            ->get();
        foreach ($cats as $cat){
            foreach ($cat->rules as $rule){
                $coltype = $rule->column;
                if ($rule->column === 'type') {
                    $type = ProductType::where('shop_id', $shopId)
                        ->where('product_type_id', $rule->condition)
                        ->first();

                    $rule->condition = $type?->product_type_name ?? 'Unknown Type';
                }

                if ($rule->column === 'vendor') {
                    $brand = Brand::where('shop_id', $shopId)
                        ->where('brand_id', $rule->condition)
                        ->first();

                    $rule->condition = $brand?->brand_name ?? 'Unknown Brand';
                }
            }
        }
        return response()->json([
            'cats' => $cats
        ],200);
    }

    public function addCatView(Request $request)
    {
        $shopId = session('shop_id');
        $pros = Product::where('shop_id','=',$shopId)
            ->select('products.product_id','products.featured_image','products.title','products.product_status')
            ->get();
        $brands = Brand::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::query()
            ->where('shop_id','=',$shopId)
            ->get();

        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        return response()->json([
            'pros' => $pros,
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
            'location' => $location,
        ]);
    }

    public function addCatNew(Request $request)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'nullable|string|max:255',
            'cat_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:manual,smart',
            'cat_rule' => 'nullable|in:and,or',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'rules' => 'nullable|array',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);

        DB::beginTransaction();
        try {
            if($request->hasFile('cat_image')){
                $file = $request->file('cat_image');
                $filename = 'category_image_'.uniqid().'.png';
                $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $cpath = 'category/'.$filename;
                Storage::disk('s3')->put($cpath,(string)$img->encode());
                $validated['cat_image'] = $cpath;
            }
            $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Cat::where('shop_id','=',$shopId)->where('cat_slug', '=', $slug)->exists()){
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $validated['cat_slug'] = $slug;

            $cat = Cat::create([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'cat_image' => $validated['cat_image'] ?? null,
                'cat_type' => $validated['cat_type'],
                'cat_rule' => $validated['cat_rule'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'shop_id' => $shopId,
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
            ]);

            if ($validated['cat_type'] === 'smart' && isset($validated['rules'])) {
                foreach ($validated['rules'] as $rule) {
                    $cat->rules()->create([
                        'column' => $rule['column'],
                        'relation' => $rule['relation'],
                        'condition' => $rule['condition'],
                        'cat_id' => $cat->cat_id,
                        'shop_id' => $shopId,
                    ]);
                }
            }
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                $validated['product_ids'] = array_unique($validated['product_ids']);
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => 99,
                        'shop_id' => $shopId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $catpro = Catpro::insert($insertData);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category added successfully',
                'cat_id' => $cat->cat_id,
                'cat' => $cat,
                'catpro' => $catpro,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function filterProductIds(Request $request)
    {
        $shopId = session('shop_id');
        $rules = $request->input('rules', []);
        $catRule = strtolower($request->input('cat_rule', 'and'));
        $query = Product::query()->where('shop_id','=',$shopId);
        $query->where(function ($outer) use ($rules, $catRule) {
            foreach($rules as $rule){
                $outerQuery = function ($q) use ($rule) {
                    $column = $rule['column'];
                    $relation = $rule['relation'];
                    $condition = $rule['condition'];
                    switch ($column) {
                        case 'tag':
                            if ($relation === 'equals') {
                                $q->whereJsonContains('tags', $condition);
                            } elseif ($relation === 'not_equals') {
                                $q->where(function ($sub) use ($condition) {
                                    $sub->whereNull('tags')
                                        ->orWhereJsonDoesntContain('tags', $condition);
                                });
                            }
                            break;
                        case 'type':
                            $q->where('product_type_id',$relation === 'not_equals' ? '!=' : '=' , $condition);
                            break;
                        case 'vendor':
                            $q->where('brand_id',$relation === 'not_equals' ? '!=' : '=' , $condition);
                            break;
                        case 'title':
                            if ($relation === 'contains') {
                                $q->where('title', 'like', "%{$condition}%");
                            } elseif ($relation === 'not_contains') {
                                $q->where('title', 'not like', "%{$condition}%");
                            }
                            break;
                        case 'price':
                            $this->applyNumericFilter($q, 'product_price', $relation, $condition);
                            break;
                        case 'stock':
                            $this->applyNumericFilter($q, 'product_stock', $relation, $condition);
                            break;
                    }
                };
                if ($catRule === 'or') {
                    $outer->orWhere($outerQuery);
                } else {
                    $outer->where($outerQuery);
                }
            }
        });

        $productIds = $query->pluck('product_id');
        return response()->json([
            'productIds' => $productIds
        ]);
    }

    private function applyNumericFilter($query, $column, $relation, $value)
    {
        switch ($relation) {
            case 'equals':
                $query->where($column, '=', $value);
                break;
            case 'greater_than':
                $query->where($column, '>', $value);
                break;
            case 'less_than':
                $query->where($column, '<', $value);
                break;
            case 'not_equals':
                $query->where($column, '!=', $value);
                break;
        }
    }

    public function getCat($cat_id)
    {
        $shopId = session('shop_id');
        $cat = Cat::with(['rules','catpros', 'products','sections','rcats','faqs'])
            ->Where('cat_id','=',$cat_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $cat['productIds'] = $cat->catpros->pluck('product_id')->toArray();
        $cat['epros'] = $cat->products;
        $pros = Product::where('shop_id','=',$shopId)
            ->select('products.product_id','products.featured_image','products.title','products.product_status')
            ->get();
        $location = Location::firstOrCreate(
            ['shop_id' => $shopId],
            [
                'location_name' => 'Default',
                'location_address' => 'Default Address',
                'location_status' => 'Active',
                'shop_id' => $shopId
            ]);
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        $brands = Brand::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $ptypes = ProductType::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $tags = Tag::query()
            ->where('shop_id','=',$shopId)
            ->get();
        $cats = Cat::query()
            ->where('shop_id','=',$shopId)
            ->get();
        return response()->json([
            'cat' => $cat,
            'pros' => $pros,
            'location' => $location,
            'stypes' => $stypes,
            'brands' => $brands,
            'ptypes' => $ptypes,
            'tags' => $tags,
            'cats'=> $cats,
        ],200);
    }

    public function updateCatProPosition(Request $request)
    {
        $shopId = session('shop_id');
        $request->validate([
            'cat_id' => 'required|integer',
            'product_id' => 'required|integer',
            'position' => 'required|integer|min:0'
        ]);
        Catpro::where('cat_id', $request->cat_id)
            ->where('shop_id', $shopId)
            ->where('product_id', $request->product_id)
            ->update([
                'position' => $request->position
            ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function addRelatedCat(Request $request)
    {
        $shopId = session('shop_id');
        if($request->memname === 'delete'){
            RelatedCat::where('related_cat_id','=',$request->related_cat_id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Related Category deleted successfully',
            ]);
        }
        if($request->hasFile('related_image')){
            $file = $request->file('related_image');
            $filename = 'related_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(400, 200, function ($constraint) {
                $constraint->aspectRatio();
            });
            $rpath = 'category/'.$filename;
            Storage::disk('s3')->put($rpath,(string)$img->encode());
        }
        RelatedCat::updateOrCreate(
            [
                'related_cat_id' => $request->related_cat_id,
                'cat_parent_id' => $request->cat_parent_id,
            ],
            [
                'cat_parent_id' => $request->cat_parent_id,
                'related_cat_title' => $request->related_cat_title,
                'cat_child_id' => $request->cat_child_id,
                'shop_id' => $shopId,
                'related_image' => $rpath ?? null,
            ]
        );
        return response()->json([
            'success' => true,
            'message' => 'Related Category added successfully',
        ]);
    }

    public function updateManualCatbyId(Request $request, $cat_id)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'required|string|max:255',
            'cat_desc' => 'nullable|string',
            'short_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:manual',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        if($request->hasFile('cat_image')){
            $file = $request->file('cat_image');
            $filename = 'category_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                $constraint->aspectRatio();
            });
            $cpath = 'category/'.$filename;
            Storage::disk('s3')->put($cpath,(string)$img->encode());
            $validated['cat_image'] = $cpath;
        }
        $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Cat::where('shop_id','=',$shopId)
            ->where('cat_slug', '=', $slug)
            ->where('cat_id', '!=', $cat_id)
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $validated['cat_slug'] = $slug;
        DB::transaction(function () use ($cat, $validated) {
            $cat->update([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'short_desc' => $validated['short_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
                'cat_image' => $validated['cat_image'] ?? $cat->cat_image,
            ]);
            $existingPositions = Catpro::where('cat_id', $cat->cat_id)
                ->pluck('position', 'product_id')
                ->toArray();
            Catpro::where('cat_id', $cat->cat_id)->delete();
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => $existingPositions[$pid] ?? 99,
                        'shop_id' => session('shop_id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Catpro::insert($insertData);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'cat_id' => $cat->cat_id,
            'cat' => $cat,
        ]);
    }

    public function updateSmartCatbyId(Request $request, $cat_id)
    {
        $shopId = session('shop_id');
        $validated = $request->validate([
            'cat_name' => 'required|string|max:255',
            'cat_slug' => 'required|string|max:255',
            'cat_desc' => 'nullable|string',
            'short_desc' => 'nullable|string',
            'cat_status' => 'required|in:Active,Inactive',
            'cat_type' => 'required|in:smart',
            'cat_rule' => 'nullable|in:and,or',
            'sort_order' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_desc' => 'nullable|string|max:500',
            'rules' => 'nullable|array',
            'rules.*.column' => 'required|string',
            'rules.*.relation' => 'required|string',
            'rules.*.condition' => 'required',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
            'cat_image' => 'nullable|file|image|max:2048',
        ]);
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        if($request->hasFile('cat_image')){
            $file = $request->file('cat_image');
            $filename = 'category_image_'.uniqid().'.png';
            $img = Image::make($file->getRealPath())->resize(600, 600, function ($constraint) {
                $constraint->aspectRatio();
            });
            $cpath = 'category/'.$filename;
            Storage::disk('s3')->put($cpath,(string)$img->encode());
            $validated['cat_image'] = $cpath;
        }
        $baseSlug = Str::slug($validated['cat_slug'] ?? $validated['cat_name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Cat::where('shop_id','=',$shopId)
            ->where('cat_slug', '=', $slug)
            ->where('cat_id', '!=', $cat_id)
            ->exists())
        {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }
        $validated['cat_slug'] = $slug;
        DB::transaction(function () use ($cat, $validated) {
            $cat->update([
                'cat_name' => $validated['cat_name'],
                'cat_slug' => $validated['cat_slug'],
                'cat_desc' => $validated['cat_desc'] ?? null,
                'short_desc' => $validated['short_desc'] ?? null,
                'cat_status' => $validated['cat_status'],
                'sort_order' => $validated['sort_order'] ?? 'title_asc',
                'cat_type' => $validated['cat_type'] ?? 'smart',
                'cat_rule' => $validated['cat_rule'] ?? 'and',
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_desc' => $validated['meta_desc'] ?? null,
                'cat_image' => $validated['cat_image'] ?? $cat->cat_image,
            ]);
            $existingPositions = Catpro::where('cat_id', $cat->cat_id)
                ->pluck('position', 'product_id')
                ->toArray();

            Catpro::where('cat_id', $cat->cat_id)->delete();
            if(!empty($validated['product_ids'])) {
                $insertData = [];
                foreach ($validated['product_ids'] as $pid) {
                    $insertData[] = [
                        'cat_id' => $cat->cat_id,
                        'product_id' => $pid,
                        'position' => $existingPositions[$pid] ?? 99,
                        'shop_id' => session('shop_id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Catpro::insert($insertData);
            }
            $cat->rules()->delete();
            if (!empty($validated['rules'])) {
                foreach ($validated['rules'] as $rule) {
                    $cat->rules()->create([
                        'column' => $rule['column'],
                        'relation' => $rule['relation'],
                        'condition' => $rule['condition'],
                        'cat_id' => $cat->cat_id,
                        'shop_id' => session('shop_id'),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'cat_id' => $cat->cat_id,
            'cat' => $cat,
        ]);

    }

    public function deleteCategorybyId($cat_id)
    {
        $shopId = session('shop_id');
        $cat = Cat::where('cat_id', $cat_id)->where('shop_id', $shopId)->firstOrFail();
        $cat->delete();
        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully',
        ]);
    }
}
