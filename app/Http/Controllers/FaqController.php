<?php

namespace App\Http\Controllers;

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
            'question' => 'required',
            'answer' => 'required',
            'product_id' => 'required|exists:products,product_id',
        ]);
        $sortOrder = ProductFaq::where('product_id', $valiData['product_id'])->max('sort_order');

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
}
