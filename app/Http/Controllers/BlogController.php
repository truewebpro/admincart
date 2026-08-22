<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Section;
use App\Models\Stype;
use App\Models\Tag;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class BlogController extends Controller
{
    public function allBlogs(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $blogs = Cache::remember(
            CacheKeys::blogs($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Blog::where('shop_id','=',$shopId)->orderBy('created_at','DESC')->get();
            }
        );
        return response()->json([
            'status' => true,
            'blogs' => $blogs,
        ]);
    }

    public function getAllBlogs(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $blogs = Blog::where('shop_id','=',$shopId)
            ->where('blog_status','=','active')
            ->latest()
            ->paginate(6);
        return response()->json([
            'status' => true,
            'blogs' => $blogs,
        ]);
    }

    public function getBlogBySlug(Request $request,$shopname,$blog_slug)
    {
        $shopId = $request->shop_id;
        $data = Cache::remember(
            CacheKeys::blog($shopId,$blog_slug),
            now()->addHours(12),
            function () use ($shopId,$blog_slug) {
                $blog = Blog::with(['faqs',
                    'comments' => function ($q) use ($shopId) {
                        $q->where('shop_id', $shopId)
                            ->approved()
                            ->whereNull('parent_id')
                            ->with(['customer', 'replies' => function ($q) {
                                $q->approved()->with('customer'); // ✅ replies also approved
                            }])
                            ->latest();
                    },
                ])
                    ->withCount([
                        'comments as comments_count' => function ($q) use ($shopId) {
                            $q->where('shop_id', $shopId)
                                ->approved()
                                ->whereNull('parent_id'); // only main comments
                        }])
                    ->where('shop_id','=',$shopId)
                    ->where('blog_slug','=',$blog_slug)
                    ->first();
                if (!$blog) {
                    return null;
                }
                $blogsections = Section::where('sectionable_id','=',$blog->blog_id)
                    ->where('sectionable_type',Blog::class)
                    ->join('stypes','stypes.stype_id','=','sections.stype_id')
                    ->select('sections.section_id','sections.sectionable_id','sections.section_json',
                        'sections.sort_order','sections.section_status','sections.stype_id','stypes.stype_slug')
                    ->where('sections.section_status','=','show')
                    ->orderBy('sections.sort_order', 'ASC')
                    ->get();
                $sectionsWithExtras = [];
                foreach ($blogsections as $section){
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
                        $sectionArray['section_json']['stype_json']['cat_slug'] = $catSlug;
                        $sectionArray['section_json']['stype_json']['catpros'] = $products;
                    }
                    $sectionsWithExtras[] = $sectionArray;
                }
                $blog->asections = $sectionsWithExtras;
                $rblogs = Blog::where('shop_id','=',$shopId)
                    ->whereNotIn('blogs.blog_id', [$blog->blog_id])
                    ->orderBy('created_at','DESC')->limit(6)->get();
                return [
                    'blog' => $blog,
                    'rblogs' => $rblogs,
                ];
            }
        );
        if (!$data) {
            return response()->json([
                'status' => false,
                'blog' => null,
            ]);
        }

        return response()->json([
            'status' => true,
            'blog' => $data['blog'],
            'rblogs' => $data['rblogs'],
        ]);
    }

    //Admin Routes
    public function blogsList()
    {
        $shopId = session('shop_id');
        $blogs = Blog::with('user')->where('shop_id','=',$shopId)->latest()->get();
        return response()->json([
            'status'=> true,
            'blogs' => $blogs,
        ]);
    }

    public function getBlogById($blog_id)
    {
        $shopId = session('shop_id');
        $blog = Blog::with('sections','faqs')->where('blog_id','=',$blog_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $ctypes = ['review_slider','blog_slider'];
        $stypes = Stype::whereNotIn('stype_slug',$ctypes)->get();
        $categories = Cat::where('shop_id','=',$shopId)
            ->select('cat_id','cat_name','cat_slug','cat_image')
            ->get();
        $brands = Brand::where('shop_id','=',$shopId)
            ->select('brand_id','brand_name','brand_slug','brand_image')
            ->get();
        $tags = Tag::where('shop_id','=',$shopId)
            ->select('tag_id','tag_name')
            ->get();
        $ptypes = ProductType::where('shop_id','=',$shopId)
            ->select('product_type_id','product_type_name')
            ->get();
        if($blog){
            return response()->json([
                'status'=> true,
                'blog' => $blog,
                'stypes'=> $stypes,
                'categories'=>$categories,
                'brands' => $brands,
                'tags' => $tags,
                'ptypes' => $ptypes,
            ]);
        } else {
            return response()->json([
                'status'=> false,
                'blog' => null,
            ]);
        }
    }

    public function addBlog(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $baseSlug = Str::slug($request['blog_slug'] ?? $request['blog_title']);
            $slug = $baseSlug;
            $counter = 1;
            while (Blog::where('shop_id','=',$shopId)->where('blog_slug', '=', $slug)->exists()){
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }
            $blogSlug = $slug;
            if($request->hasFile('blog_image')){
                $file = $request->file('blog_image');
                $filename = 'blog_image_'.uniqid().'.png';
                $img = Image::make($file->getRealPath())->resize(1200, 800, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $bpath = 'blog/'.$filename;
                Storage::disk('s3')->put($bpath,(string)$img->encode());
                $blogImage = $bpath;
            }
            $blog = Blog::create(
                [
                    'blog_title' => $request->blog_title,
                    'blog_slug' => $blogSlug,
                    'blog_description' => $request->blog_description,
                    'blog_excerpt' => $request->blog_excerpt,
                    'blog_image' => $blogImage ?? $request['blog_image'],
                    'btags' => $request->btags ?? null,
                    'blog_status' => $request->blog_status ?? 'active',
                    'meta_title' => $request->meta_title,
                    'meta_desc' => $request->meta_desc,
                    'user_id' => $request->user_id ?? auth()->user()->id,
                    'shop_id' => $shopId,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'blog' => $blog,
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

    public function updateBlog(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $blog = Blog::where('shop_id', $shopId)
                ->where('blog_id', $request->blog_id)
                ->firstOrFail();

            if (!empty($request->blog_slug) && $request->blog_slug !== $blog->blog_slug) {
                $baseSlug = Str::slug($request->blog_slug);
                $slug = $baseSlug;
                $counter = 1;

                while (Blog::where('shop_id', $shopId)
                    ->where('blog_id', '!=', $blog->blog_id)
                    ->where('blog_slug', $slug)
                    ->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $blogSlug = $slug;
            } else {

                $blogSlug = $blog->blog_slug;
            }
            if ($request->hasFile('blog_image')) {
                $file = $request->file('blog_image');
                $filename = 'blog_image_' . uniqid() . '.png';
                $img = Image::make($file->getRealPath())->resize(1200, 800, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $bpath = 'blog/' . $filename;
                Storage::disk('s3')->put($bpath, (string) $img->encode());
                $blogImage = $bpath;
            } else {
                $blogImage = $blog->blog_image;
            }
            $blog = $blog->update(
                [
                    'blog_title' => $request->blog_title,
                    'blog_slug' => $blogSlug,
                    'blog_description' => $request->blog_description,
                    'blog_excerpt' => $request->blog_excerpt,
                    'blog_image' => $blogImage,
                    'btags' => $request->btags ?? null,
                    'blog_status' => $request->blog_status ?? 'active',
                    'meta_title' => $request->meta_title,
                    'meta_desc' => $request->meta_desc,
                    'user_id' => $request->user_id ?? auth()->user()->id,
                    'shop_id' => $shopId,
                ]
            );
            DB::commit();
            return response()->json([
                'success' => true,
                'blog' => $blog,
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
}
