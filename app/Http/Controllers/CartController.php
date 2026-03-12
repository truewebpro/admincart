<?php

namespace App\Http\Controllers;

use App\Models\Acart;
use App\Models\AcartEvent;
use App\Models\AcartItem;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $shopId = $request->shop_id;
        $cartToken = $request->cart_token;
        if(!$cartToken){
            return response()->json([
                'success' => false,
                'message' => 'cart_token missing'
            ],400);
        }
        $cart = Acart::where('shop_id', $shopId)->where('cart_token', $cartToken)->first();
        if(!$cart){
            return response()->json([
                'success' => true,
                'cart' => null,
                'items' => []
            ]);
        }
        /* cart expiration check */
        if($cart->expires_at && $cart->expires_at < now()){
            $cart->update([
                'cart_status' => 'abandoned',
                'items_count'=>0,
                'subtotal'=>0,
                'cart_total'=>0
            ]);

            $cart->items()->delete();
        }
        $cart->update([
            'last_activity_at' => now()
        ]);
        $items = $cart->items()
            ->with([
            'product:product_id,title,handle,featured_image',
            'variant:variant_id,sku,variant_image,option_values'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'cart' => [
                'acart_id' => $cart->acart_id,
                'cart_token' => $cart->cart_token,
                'items_count' => $cart->items_count,
                'subtotal' => $cart->subtotal,
                'discount_amount' => $cart->discount_amount,
                'shipping_amount' => $cart->shipping_amount,
                'tax_amount' => $cart->tax_amount,
                'cart_total' => $cart->cart_total,
                'cart_version' => $cart->cart_version,
            ],
            'items' => $items
        ]);
    }

    public function event(Request $request)
    {
        $shopId = $request->shop_id;
        $cartToken = $request->cart_token;
        if(!$cartToken){
            return response()->json([
                'success' => false,
                'message' => 'cart_token missing'
            ]);
        }
        $cart = Acart::firstOrCreate(
            ['shop_id' => $shopId,'cart_token' => $cartToken],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $this->deviceType($request->userAgent()),
                'browser' => $this->browser($request->userAgent()),
                'platform' => $this->platform($request->userAgent()),
                'last_activity_at' => now()
            ]
        );
        switch ($request->type){
            case "add":
                $this->addItem($cart,$request);
                $this->logEvent($cart,'item_added',[
                    'variant_id'=>$request->variant_id,
                    'quantity'=>$request->quantity ?? 1
                ]);
                break;

            case "remove":
                $this->removeItem($cart, $request);
                $this->logEvent($cart,'item_removed',[
                    'variant_id'=>$request->variant_id
                ]);
                break;

            case "update":
                $this->updateItem($cart, $request);
                $this->logEvent($cart,'quantity_updated',[
                    'variant_id'=>$request->variant_id,
                    'quantity'=>$request->newQty
                ]);
                break;
            case "checkout_started":
                $this->logEvent($cart,'checkout_started',[
                    'process' => 'Reached to Checkout',
                ]);
                break;

            default:
                return response()->json([
                    'success'=>false,
                    'message'=>'Invalid event type'
                ],400);
        }

        $this->recalculateCart($cart);

        return response()->json([
            'success' => true,
        ]);
    }

    private function addItem($cart, $request)
    {
        $variant = Variant::with('product')
            ->where('variant_id', $request->variant_id)
            ->firstOrFail();

        $item = AcartItem::firstOrNew([
            'acart_id' => $cart->acart_id,
            'variant_id' => $variant->variant_id
        ]);

        $qty = $request->quantity ?? 1;
        $stock = $this->getVariantStock($variant->variant_id);
        $newQty = ($item->exists ? $item->quantity : 0) + $qty;
        if($stock !== null && $newQty > $stock){
            return response()->json([
                'success'=>false,
                'message'=>'Not enough stock'
            ],422);
        }

        $item->shop_id = $cart->shop_id;
        $item->product_id = $variant->product_id;
        $item->title = $variant->product->title;
        $item->price = $variant->price;
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->line_total = $item->price * $item->quantity;
        $item->options_json = $request->options ?? [];

        $item->save();
    }

    private function removeItem($cart, $request)
    {
        AcartItem::where('acart_id',$cart->acart_id)
            ->where('variant_id',$request->variant_id)
            ->delete();
    }

    private function updateItem($cart, $request)
    {
        $item = AcartItem::where('acart_id',$cart->acart_id)
            ->where('variant_id',$request->variant_id)
            ->first();

        if(!$item) return;
        if($request->newQty <= 0){
            $item->delete();
            return;
        }

        $item->quantity = $request->newQty;
        $item->line_total = $item->price * $item->quantity;
        $item->save();
    }

    private function recalculateCart($cart)
    {
        $subtotal = AcartItem::where('acart_id',$cart->acart_id)
            ->sum('line_total');

        $itemCount = AcartItem::where('acart_id',$cart->acart_id)
            ->sum('quantity');

        $cart->update([
            'items_count' => $itemCount,
            'subtotal' => $subtotal,
            'cart_total' => $subtotal,
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(48),
            'cart_version' => $cart->cart_version + 1
        ]);
    }

    private function deviceType($agent)
    {
        if(str_contains($agent,'Mobile')) return 'mobile';
        if(str_contains($agent,'Tablet')) return 'tablet';
        return 'desktop';
    }

    private function browser($agent)
    {
        if(str_contains($agent,'Chrome')) return 'Chrome';
        if(str_contains($agent,'Firefox')) return 'Firefox';
        if(str_contains($agent,'Safari')) return 'Safari';
        return 'Other';
    }

    private function platform($agent)
    {
        if(str_contains($agent,'Windows')) return 'Windows';
        if(str_contains($agent,'Mac')) return 'Mac';
        if(str_contains($agent,'Android')) return 'Android';
        if(str_contains($agent,'iPhone')) return 'iOS';
        return 'Other';
    }

    private function logEvent($cart, $type, $data = [])
    {
        AcartEvent::create([
            'acart_id' => $cart->acart_id,
            'shop_id' => $cart->shop_id,
            'event_type' => $type,
            'event_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    private function getVariantStock($variantId)
    {
        return DB::table('stocks')
            ->where('variant_id',$variantId)
            ->sum('quantity');
    }
}
