<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesShopContext;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyProductPoint;
use App\Models\Variant;
use Illuminate\Http\Request;

/**
 * Lets admin override how many points a specific variant earns, independent
 * of the global spend_amount/points_per_spend rate. Can be added, changed, or
 * removed at any time — takes effect on the next order, no redeploy needed.
 */
class LoyaltyProductPointController extends Controller
{
    use ResolvesShopContext;

    // GET /admin/loyalty/product-points?search=xxx
    public function index(Request $request)
    {
        $shopId = $this->currentShopId();

        $query = LoyaltyProductPoint::with('variant')->where('shop_id', $shopId);

        // Searches by SKU only — adjust to join through the product relation
        // if you want to search by product title too (variants.product_id -> products.title).
        if ($request->filled('search')) {
            $query->whereHas('variant', function ($q) use ($request) {
                $q->where('sku', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    // POST /admin/loyalty/product-points
    // Body: { variant_id, points_per_unit, is_active, notes }
    public function store(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:variants,variant_id',
            'points_per_unit' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shopId = $this->currentShopId();

        // Confirms the variant actually belongs to this admin's shop before
        // creating an override for it (adjust the shop_id path if variants
        // are scoped via their parent product rather than directly).
        $variant = Variant::where('shop_id', $shopId)->findOrFail($data['variant_id']);

        $override = LoyaltyProductPoint::updateOrCreate(
            ['shop_id' => $shopId, 'variant_id' => $variant->variant_id],
            $data
        );

        return response()->json($override->load('variant'), 201);
    }

    // PUT /admin/loyalty/product-points/{override}
    public function update(Request $request, LoyaltyProductPoint $override)
    {
        abort_unless($override->shop_id === $this->currentShopId(), 403);

        $data = $request->validate([
            'points_per_unit' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $override->update($data);

        return response()->json($override->load('variant'));
    }

    // DELETE /admin/loyalty/product-points/{override}
    // Removing the override just means this variant falls back to the global rate.
    public function destroy(LoyaltyProductPoint $override)
    {
        abort_unless($override->shop_id === $this->currentShopId(), 403);
        $override->delete();

        return response()->json(['message' => 'Override removed — variant now uses the global earn rate.']);
    }
}
