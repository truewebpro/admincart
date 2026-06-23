<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Cat;
use App\Models\Product;
use App\Models\Section;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
                $blog = Blog::with([
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
}
