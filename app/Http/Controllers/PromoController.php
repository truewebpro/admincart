<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Models\PromoItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function getPromo(Request $request):JsonResponse
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'page_type' => 'required|in:products,cats,brands,blogs,pages,policies,homepage',
            'position' => 'required|in:top,bottom',
        ]);
        $promo = Promo::firstOrCreate(
            [
                'shop_id' => $shopId,
                'page_type' => $data['page_type'],
                'position' => $data['position'],
            ],
            [
                'style' => 'style1',
            ]
        );

        if ($promo->items()->count() === 0) {
            $promo->items()->create([
                'media_type' => 'icon',
                'media_value' => 'mdi:truck-fast',
                'title' => 'Free UK Delivery',
                'subtext' => 'Orders over £50',
                'sort_order' => 1,
            ]);
        }
        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo added successfully.',
            'promo' => $promo,
        ]);
    }

    public function updatePromo(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'id' => 'required|integer',
            'heading' => 'nullable|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'style' => 'required|string|max:50',
            'bg_color' => 'required|string|max:20',
            'title_color' => 'required|string|max:20',
            'subtext_color' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        $promo = Promo::where('shop_id', $shopId)
            ->where('id', $data['id'])
            ->firstOrFail();

        $promo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Promo updated successfully.',
            'promo' => $promo,
        ]);
    }

    public function addPromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'promo_id' => 'required|integer',
            'media_type' => 'required|in:icon,svg,image',
            'media_value' => 'required|string',
            'title' => 'required|string|max:255',
            'subtext' => 'nullable|string',
        ]);
        $promo = Promo::where('shop_id', $shopId)
            ->where('id', $data['promo_id'])
            ->firstOrFail();

        $sortOrder = ($promo->items()->max('sort_order') ?? 0) + 1;

        $data['media_value'] = trim($data['media_value']);
        $promo->items()->create([
            'media_type' => $data['media_type'],
            'media_value' => $data['media_value'],
            'title' => $data['title'],
            'subtext' => $data['subtext'],
            'sort_order' => $sortOrder,
        ]);

        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item added successfully.',
            'promo' => $promo,
        ]);
    }

    public function updatePromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'id' => 'required|integer',
            'media_type' => 'required|in:icon,svg,image',
            'media_value' => 'required|string',
            'title' => 'required|string|max:255',
            'subtext' => 'nullable|string',
        ]);

        $item = PromoItem::whereHas('promo', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        })
            ->where('id', $data['id'])
            ->firstOrFail();

        $item->update($data);
        $promo = $item->promo;
        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item updated successfully.',
            'promo' => $promo,
        ]);
    }

    public function deletePromoItem(Request $request)
    {
        $shopId = session('shop_id');
        $data = $request->validate([
            'id' => 'required|integer',
            ]);

        $item = PromoItem::whereHas('promo', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        })
            ->where('id', $data['id'])
            ->firstOrFail();

        if ($item->promo->items()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one promo item is required.'
            ], 422);
        }

        $promo = $item->promo;
        $item->delete();
        $promo->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Promo item deleted successfully.',
            'promo' => $promo,
        ]);
    }

    public function getPromoByType(Request $request)
    {
        $shopId = $request->shop_id;
        $data = $request->validate([
            'page_type' => 'required|in:products,cats,brands,blogs,pages,policies,homepage',
            'position' => 'required|in:top,bottom',
        ]);
        $pageType = $data['page_type'];
        $pagePosition = $data['position'];
        $promo = Promo::with('items')
            ->where('page_type', '=', $pageType)
            ->where('position', '=', $pagePosition)
            ->where('shop_id','=',$shopId)
            ->where('status','=',true)
            ->first();
        return response()->json([
            'success' => true,
            'promo' => $promo,
        ]);
    }
}
