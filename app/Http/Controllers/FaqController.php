<?php

namespace App\Http\Controllers;

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

        $sortOrder = ProductFaq::where('product_id', $product->shop_id)->max('sort_order');

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
}
