<?php

namespace App\Http\Controllers;

use App\Models\HomePromo;
use App\Models\HomePromoItem;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function getHomePagePromo(Request $request)
    {
        $shopId = session('shop_id');

        $promo = HomePromo::firstOrCreate(
            [
                'shop_id' => $shopId,
                'homepage_id' => $request->homepage_id,
                'position' => $request->position,
            ],
            [
                'style' => 'style1',
            ]
        );

        if ($promo->items()->count() === 0) {
            $promo->items()->create([
                'media_type' => 'icon',
                'media_value' => 'mdi:account',
                'title' => 'Free Delivery',
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

        $promo = HomePromo::where('shop_id', $shopId)
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
            'home_promo_id' => 'required|integer',
            'media_type' => 'required|in:icon,svg,image',
            'media_value' => 'required|string',
            'title' => 'required|string|max:255',
            'subtext' => 'nullable|string',
        ]);

        $promo = HomePromo::where('shop_id', $shopId)
            ->where('id', $data['home_promo_id'])
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

        $item = HomePromoItem::whereHas('promo', function ($q) use ($shopId) {
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
        $data = $request->validate(['id' => 'required|integer',]);

        $item = HomePromoItem::whereHas('promo', function ($q) use ($shopId) {
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
}
