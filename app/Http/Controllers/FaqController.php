<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogFaq;
use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\Cat;
use App\Models\CatFaq;
use App\Models\Page;
use App\Models\PageFaq;
use App\Models\Product;
use App\Models\ProductFaq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'resolve.admin.shop']);
    }
    public function addProductFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'product_id' => 'required|exists:products,product_id',
        ]);
        $product = Product::where('product_id', $valiData['product_id'])
            ->where('shop_id', $shopId)
            ->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $sortOrder = ProductFaq::where('product_id', $product->product_id)->max('sort_order');

        $faq = ProductFaq::create([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
            'product_id' => $valiData['product_id'],
            'shop_id' => $shopId,
            'status' => true,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Product Faq added successfully',
            'faq' => $faq,
        ]);
    }

    public function editProductFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'id' => 'required|exists:product_faqs,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = ProductFaq::where('id', $valiData['id'])
            ->where('product_id', $valiData['product_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Product Faq not found']);}

       $faq->update([
           'question' => $valiData['question'],
           'answer' => $valiData['answer'],
       ]);

        return response()->json([
            'success' => true,
            'message' => 'Product Faq edited successfully',
        ]);
    }

    public function deleteProductFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'id' => 'required|exists:product_faqs,id',
            'product_id' => 'required|exists:products,product_id',
        ]);
        $faq = ProductFaq::where('id', $valiData['id'])
            ->where('product_id', $valiData['product_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Product Faq not found']);}
        $faq->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product Faq deleted successfully',
        ]);
    }

    public function addBlogFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'blog_id' => 'required|exists:blogs,blog_id',
        ]);
        $blog = Blog::where('blog_id', $valiData['blog_id'])
            ->where('shop_id', $shopId)
            ->first();
        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $sortOrder = BlogFaq::where('blog_id', $blog->blog_id)->max('sort_order');

        $faq = BlogFaq::create([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
            'blog_id' => $valiData['blog_id'],
            'shop_id' => $shopId,
            'status' => true,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Blog Faq added successfully',
            'faq' => $faq,
        ]);
    }

    public function editBlogFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'blog_id' => 'required|exists:blogs,blog_id',
            'id' => 'required|exists:blog_faqs,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = BlogFaq::where('id', $valiData['id'])
            ->where('blog_id', $valiData['blog_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Blog Faq not found']);}

        $faq->update([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Blog Faq edited successfully',
        ]);
    }

    public function deleteBlogFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'id' => 'required|exists:blog_faqs,id',
            'blog_id' => 'required|exists:blogs,blog_id',
        ]);
        $faq = BlogFaq::where('id', $valiData['id'])
            ->where('blog_id', $valiData['blog_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Blog Faq not found']);}
        $faq->delete();
        return response()->json([
            'success' => true,
            'message' => 'Blog Faq deleted successfully',
        ]);
    }

    public function addPageFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'page_id' => 'required|exists:pages,page_id',
        ]);
        $page = Page::where('page_id', $valiData['page_id'])
            ->where('shop_id', $shopId)
            ->first();
        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }

        $sortOrder = PageFaq::where('page_id', $page->page_id)->max('sort_order');

        $faq = PageFaq::create([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
            'page_id' => $valiData['page_id'],
            'shop_id' => $shopId,
            'status' => true,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Page Faq added successfully',
            'faq' => $faq,
        ]);
    }

    public function editPageFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'page_id' => 'required|exists:pages,page_id',
            'id' => 'required|exists:page_faqs,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = PageFaq::where('id', $valiData['id'])
            ->where('page_id', $valiData['page_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Page Faq not found']);}

        $faq->update([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page Faq edited successfully',
        ]);
    }

    public function deletePageFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'id' => 'required|exists:page_faqs,id',
            'page_id' => 'required|exists:pages,page_id',
        ]);
        $faq = PageFaq::where('id', $valiData['id'])
            ->where('page_id', $valiData['page_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Page Faq not found']);}
        $faq->delete();
        return response()->json([
            'success' => true,
            'message' => 'Page Faq deleted successfully',
        ]);
    }

    public function addCatFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'cat_id' => 'required|exists:cats,cat_id',
        ]);
        $cat = Cat::where('cat_id', $valiData['cat_id'])
            ->where('shop_id', $shopId)
            ->first();
        if (!$cat) {
            return response()->json([
                'success' => false,
                'message' => 'Cat not found'
            ], 404);
        }

        $sortOrder = CatFaq::where('cat_id', $cat->cat_id)->max('sort_order');

        $faq = CatFaq::create([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
            'cat_id' => $valiData['cat_id'],
            'shop_id' => $shopId,
            'status' => true,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Cat Faq added successfully',
            'faq' => $faq,
        ]);
    }

    public function editCatFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'cat_id' => 'required|exists:cats,cat_id',
            'id' => 'required|exists:cat_faqs,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = CatFaq::where('id', $valiData['id'])
            ->where('cat_id', $valiData['cat_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Cat Faq not found']);}

        $faq->update([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cat Faq edited successfully',
        ]);
    }

    public function deleteCatFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'id' => 'required|exists:cat_faqs,id',
            'cat_id' => 'required|exists:cats,cat_id',
        ]);
        $faq = CatFaq::where('id', $valiData['id'])
            ->where('cat_id', $valiData['cat_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Cat Faq not found']);}
        $faq->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cat Faq deleted successfully',
        ]);
    }

    public function addBrandFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'brand_id' => 'required|exists:brands,brand_id',
        ]);
        $brand = Brand::where('brand_id', $valiData['brand_id'])
            ->where('shop_id', $shopId)
            ->first();
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ], 404);
        }

        $sortOrder = BrandFaq::where('brand_id', $brand->brand_id)->max('sort_order');

        $faq = BrandFaq::create([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
            'brand_id' => $valiData['brand_id'],
            'shop_id' => $shopId,
            'status' => true,
            'sort_order' => ($sortOrder ?? 0) + 1,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Brand Faq added successfully',
            'faq' => $faq,
        ]);
    }

    public function editBrandFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'brand_id' => 'required|exists:brands,brand_id',
            'id' => 'required|exists:brand_faqs,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ]);

        $faq = BrandFaq::where('id', $valiData['id'])
            ->where('brand_id', $valiData['brand_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Brand Faq not found']);}

        $faq->update([
            'question' => $valiData['question'],
            'answer' => $valiData['answer'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand Faq edited successfully',
        ]);
    }

    public function deleteBrandFaq(Request $request)
    {
        $shopId = session('shop_id');
        $valiData = $request->validate([
            'id' => 'required|exists:brand_faqs,id',
            'brand_id' => 'required|exists:brands,brand_id',
        ]);
        $faq = BrandFaq::where('id', $valiData['id'])
            ->where('brand_id', $valiData['brand_id'])
            ->where('shop_id', $shopId)
            ->first();
        if(!$faq){return response()->json(['success' => false, 'message' => 'Brand Faq not found']);}
        $faq->delete();
        return response()->json([
            'success' => true,
            'message' => 'Brand Faq deleted successfully',
        ]);
    }
}
