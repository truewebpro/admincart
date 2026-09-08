<?php

namespace App\Http\Controllers;

use App\Enums\BadgePosition;
use App\Enums\LabelRuleColumn;
use App\Enums\LabelRuleRelation;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLabel;
use App\Models\ProductLabelRule;
use App\Models\ProductType;
use App\Services\ProductLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

class ProductLabelController extends Controller
{
    public function list()
    {
        $shopId = session('shop_id');

        $labels = ProductLabel::withCount('products')
            ->with(['rules', 'products:product_id,title'])
            ->where('shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'label' => $l->label,
                    'use_label' => $l->use_label,
                    'color' => $l->color,
                    'bg_color' => $l->bg_color,
                    'style' => $l->style,
                    'image' => $l->image,
                    'position' => $l->position->value,
                    'is_active' => $l->is_active,
                    'products_count' => $l->products_count,
                    'is_smart' => $l->rules->isNotEmpty(),
                    'assigned_products' => $l->products->map(fn ($p) => [
                        'product_id' => $p->product_id,
                        'title' => $p->title,
                    ]),
                    'rules' => $l->rules->map(fn ($r) => [
                        'id' => $r->id,
                        'column' => $r->column->value,
                        'relation' => $r->relation->value,
                        'condition' => $r->condition,
                        'join_type' => $r->join_type,
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'positions' => collect(BadgePosition::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
            // for the rule builder's 'brand'/'type' condition value pickers
            'brands' => Brand::where('shop_id', $shopId)->get(['brand_id', 'brand_name']),
            'product_types' => ProductType::where('shop_id', $shopId)->get(['product_type_id', 'product_type_name']),
        ]);
    }

    /** Products for the manual-assign multi-select — only meaningful for
     *  a label with no rules (a rule-driven label's products are managed
     *  by ProductLabelService::syncProduct(), not picked by hand). */
    public function products(Request $request)
    {
        $shopId = session('shop_id');

        $products = Product::where('shop_id', $shopId)
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->limit(50)
            ->get(['product_id', 'title', 'featured_image']);

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function save(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'nullable|exists:product_labels,id',
            'label' => 'required|string|max:255',
            'use_label' => 'boolean',
            'color' => 'nullable|string|max:7',
            'bg_color' => 'nullable|string|max:7',
            'style' => 'nullable|string|max:100',
            'position' => ['required', Rule::enum(BadgePosition::class)],
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048', // 2MB, adjust to your existing convention
        ]);

        $data = [
            'label' => $request->label,
            'use_label' => $request->use_label ?? true,
            'color' => $request->color,
            'bg_color' => $request->bg_color,
            'style' => $request->style,
            'position' => $request->position,
            'is_active' => $request->is_active ?? true,
        ];

        if ($request->hasFile('image')) {
            $Image = $request->file('image');
            $filename = 'label_'.uniqid().'.png';
            $img = Image::make($Image->getRealPath())->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $bpath = 'images/labels/'.$filename;
            Storage::disk('s3')->put($bpath, (string) $img->encode());
            $data['image'] = $bpath;
        }

        if ($request->id) {
            $label = ProductLabel::where('id', $request->id)
                ->where('shop_id', $shopId)
                ->firstOrFail();
            $label->update($data);
        } else {
            $label = ProductLabel::create(array_merge($data, ['shop_id' => $shopId]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Product label saved successfully',
            'id' => $label->id,
        ]);
    }

    public function delete(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate(['id' => 'required|exists:product_labels,id']);

        $label = ProductLabel::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->first();

        if (!$label) {
            return response()->json(['success' => false, 'message' => 'Label not found']);
        }

        if ($label->image) {
            Storage::disk('s3')->delete($label->image);
        }

        $label->delete(); // cascades product_label_rules + product_label_products via FK

        return response()->json(['success' => true, 'message' => 'Product label deleted successfully']);
    }

    /**
     * Replaces this label's full rule set — delete-then-insert rather than
     * per-rule CRUD, since the rule builder UI naturally submits "here's
     * the complete current set" each save. Re-syncs every product in the
     * shop against the new rules immediately after, so results aren't
     * stale until the next unrelated product save.
     */
    public function saveRules(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'required|exists:product_labels,id',
            'rules' => 'nullable|array',
            'rules.*.column' => ['required_with:rules', Rule::enum(LabelRuleColumn::class)],
            'rules.*.relation' => ['required_with:rules', Rule::enum(LabelRuleRelation::class)],
            'rules.*.condition' => 'required_with:rules',
            'rules.*.join_type' => 'nullable|in:and,or',
        ]);

        $label = ProductLabel::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->firstOrFail();

        DB::transaction(function () use ($request, $label, $shopId) {
            ProductLabelRule::where('product_label_id', $label->id)->delete();

            foreach ($request->rules ?? [] as $rule) {
                ProductLabelRule::create([
                    'product_label_id' => $label->id,
                    'shop_id' => $shopId,
                    'column' => $rule['column'],
                    'relation' => $rule['relation'],
                    'condition' => $rule['condition'],
                    'join_type' => $rule['join_type'] ?? 'and',
                ]);
            }
        });

        // re-evaluate every product against the new rule set immediately —
        // without this, results only refresh whenever each product next
        // gets saved elsewhere, which could be a long time
        $service = app(ProductLabelService::class);
        Product::where('shop_id', $shopId)->each(fn (Product $p) => $service->syncProduct($p));

        return response()->json(['success' => true, 'message' => 'Rules saved and products re-synced']);
    }

    /** Manual assignment — only meaningful for a label with no rules. */
    public function assignProducts(Request $request)
    {
        $shopId = session('shop_id');

        $request->validate([
            'id' => 'required|exists:product_labels,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,product_id',
        ]);

        $label = ProductLabel::where('id', $request->id)
            ->where('shop_id', $shopId)
            ->firstOrFail();

        if ($label->rules()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This label has rules — remove them first to assign products manually',
            ]);
        }

        // sync() needs shop_id on each pivot row explicitly (product_label_products
        // has a NOT NULL shop_id column beyond the two FKs)
        $sync = collect($request->product_ids ?? [])
            ->mapWithKeys(fn ($id) => [$id => ['shop_id' => $shopId]]);
        $label->products()->sync($sync);

        return response()->json(['success' => true, 'message' => 'Products assigned']);
    }

}
