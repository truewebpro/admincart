<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function addReviewByCustomer(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $customerId = $request->customer_id;

        $validated = $request->validate([
            'product_id'    => ['required', 'integer', 'exists:products,product_id'],
            'rating'        => ['required', 'numeric', 'min:1', 'max:5'],
            'review_title'  => ['required', 'string', 'max:255'],
            'review_text'   => ['nullable', 'string'],
        ]);

        $review = Review::updateOrCreate(
            [
                'shop_id'     => $shopId,
                'product_id'  => $validated['product_id'],
                'customer_id' => $customerId,
            ],
            [
                'review_title'  => $validated['review_title'],
                'review_text'   => $validated['review_text'] ?? null,
                'rating'        => $validated['rating'],
                'review_status' => 'pending', // re-moderate on edits too, not just first submission
            ]
        );

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    public function updateReviewByCustomer(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $customerId = $request->customer_id;

        $validated = $request->validate([
            'product_id'    => ['required', 'integer', 'exists:products,product_id'],
            'rating'        => ['required', 'numeric', 'min:1', 'max:5'],
            'review_title'  => ['required', 'string', 'max:255'],
            'review_text'   => ['nullable', 'string'],
        ]);

        $review = Review::updateOrCreate(
            [
                'shop_id'     => $shopId,
                'product_id'  => $validated['product_id'],
                'customer_id' => $customerId,
            ],
            [
                'review_title'  => $validated['review_title'],
                'review_text'   => $validated['review_text'] ?? null,
                'rating'        => $validated['rating'],
                'review_status' => 'pending', // re-moderate on edits too, not just first submission
            ]
        );

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    public function getProductReviews(Request $request,$shopname,$slug)
    {
        $shopId = $request->shop_id;
        $user = auth('customer')->user();
        $product = Product::where('shop_id',$shopId)
            ->where('handle','=',$slug)
            ->first();
        if(!$product){
            return response()->json(['success'=>false,'message'=>'Product not found']);
        }

        $baseQuery = Review::where('product_id', $product->product_id);
//            ->where('review_status', 'verified');

        $reviews = (clone $baseQuery)
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get();

        $stats = (clone $baseQuery)
            ->selectRaw('
            COUNT(*) as reviews_count,
            AVG(rating) as reviews_avg_rating,
            SUM(CASE WHEN ROUND(rating) = 5 THEN 1 ELSE 0 END) as stars5,
            SUM(CASE WHEN ROUND(rating) = 4 THEN 1 ELSE 0 END) as stars4,
            SUM(CASE WHEN ROUND(rating) = 3 THEN 1 ELSE 0 END) as stars3,
            SUM(CASE WHEN ROUND(rating) = 2 THEN 1 ELSE 0 END) as stars2,
            SUM(CASE WHEN ROUND(rating) = 1 THEN 1 ELSE 0 END) as stars1
        ')
            ->first();
        $userReview = null;
        if ($user) {
            $userReview = Review::where('product_id', $product->product_id)
                ->where('customer_id', $user->customer_id)
                ->first();
        }

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'reviews_count' => (int) $stats->reviews_count,
            'reviews_avg_rating' => round((float) $stats->reviews_avg_rating, 1),
            'stars5' => (int) $stats->stars5,
            'stars4' => (int) $stats->stars4,
            'stars3' => (int) $stats->stars3,
            'stars2' => (int) $stats->stars2,
            'stars1' => (int) $stats->stars1,
            'user_review' => $userReview,
        ]);
    }
}
