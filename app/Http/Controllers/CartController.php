<?php

namespace App\Http\Controllers;

use App\Models\Acart;
use App\Models\AcartEvent;
use App\Models\AcartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Stock;
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
        $cart = Acart::where('shop_id', $shopId)->where('is_active', true)->where('cart_token', $cartToken)->first();
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
        $cart = Acart::where('shop_id', $shopId)
            ->where('cart_token', $cartToken)
            ->where('is_active', true)
            ->first();
        if(!$cart){
            $cart = Acart::create([
                'shop_id' => $shopId,
                'cart_token' => $cartToken,
                'is_active' => true,
                'cart_status' => 'active',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $this->deviceType($request->userAgent()),
                'browser' => $this->browser($request->userAgent()),
                'platform' => $this->platform($request->userAgent()),
                'last_activity_at' => now()
            ]);
        }
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

            case "shipping_selected":
                $this->updateShipping($cart, $request);
                break;

            case "payment_selected":
                $this->updatePayment($cart, $request);
                break;

            case "customer_attached":
                $this->attachCustomer($cart, $request);
                break;

            case "pay_bank_transfer":
                return $this->bankCheckout($cart, $request);

            default:
                return response()->json([
                    'success'=>false,
                    'message'=>'Invalid event type'
                ],400);
        }

        $this->recalculateCart($cart);

        return response()->json([
            'success' => true,
            'cart' => $cart,
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

    private function updateShipping($cart, $request)
    {
        $cart->update([
            'shipping_method' => $request->shipping_method,
            'shipping_cost'   => $request->shipping_cost,
            'cart_status'     => 'shipping_selected'
        ]);

        $this->logEvent($cart, 'shipping_selected', [
            'method' => $request->shipping_method,
            'cost'   => $request->shipping_cost
        ]);
    }

    private function updatePayment($cart, $request)
    {
        $cart->update([
            'payment_method' => $request->payment_method,
            'cart_status'    => 'payment_selected'
        ]);

        $this->logEvent($cart, 'payment_selected', [
            'method' => $request->payment_method
        ]);
    }

    private function attachCustomer($cart, $request)
    {
        $cart->update([
            'customer_id' => $request->customer_id,
            'cart_status' => 'customer_attached'
        ]);

        // optional: store address snapshot (recommended)
//        $cart->meta = [
//            'address_id' => $request->address_id,
//            'email' => $request->email
//        ];
//
//        $cart->save();

        $this->logEvent($cart, 'customer_attached', [
            'customer_id' => $request->customer_id,
            'address_id' => $request->address_id
        ]);
    }

    private function bankCheckout($cart, $request)
    {
        if($cart->order_id){
            return response()->json([
                'success'=>true,
                'order_id'=>$cart->order_id
            ]);
        }
        DB::beginTransaction();
        try {
            $items = $request['order_items'];
            $cartData = $request['cart_data'];
            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }
            $prefix = Shop::where('shop_id', $cart->shop_id)->value('order_prefix') ?? "#";
            $lastOrder = Order::withTrashed()->where('shop_id', $cart->shop_id)->orderByDesc('order_id')->first();
            $lastOrderNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder->order_number)): 1000;
            $orderNumber = $prefix . ($lastOrderNumber + 1);
            $order = Order::create([
                'order_number' => $orderNumber,
                'shop_id' => $cart->shop_id,
                'customer_id' => $cart->customer_id,
                'address_id' => $cartData['address_id'],
                'order_status' => 'pending',
                'label_status' => 'no_label',
                'payment_method' => $cart->payment_method,
                'payment_status' => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'shipping_method' => $cart->shipping_method,
                'shipping_cost' => $cart->shipping_cost,
                'coupon_id' => $cartData['coupon_id'],
                'coupon_code' => $cartData['coupon_code'],
                'discount_amount' => $cart->discount_amount,
                'subtotal' => $cartData['subtotal'],
                'order_total' => $cartData['order_total'],
                'tax_amount' => $cartData['tax_amount'],
                'currency_code' => $cart->currency,
                'is_guest_order' => $cartData['is_guest_order'],
                'shipping_name' => $cartData['shipping_name'],
                'shipping_address_line1' => $cartData['shipping_address_line1'],
                'shipping_address_line2' => $cartData['shipping_address_line2'],
                'shipping_city' => $cartData['shipping_city'],
                'shipping_postcode' => $cartData['shipping_postcode'],
                'shipping_country' => $cartData['shipping_country'],
                'notes' => $cartData['notes'],
                'checkout_id' => $request->checkout_id,
                'placed_at' => now(),
            ]);
            foreach ($items as $item) {
                $stock = Stock::where('variant_id', $item['variant_id'])
                    ->where('shop_id', $cart->shop_id)
                    ->lockForUpdate()
                    ->first();
                $available = $stock ? $stock->quantity : 0;
                $ordered = $item['quantity'];
                $allocated = min($available, $ordered);
                $backorder = $ordered - $allocated;
                $shipped = 0;
                // Deduct only allocated
                if ($stock && $allocated > 0) {
                    $stock->decrement('quantity', $allocated);
                }
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'title' => $item['title'],
                    'options' => $item['options'],
                    'price' => $item['price'],
                    'quantity' => $ordered,
                    'total' => $item['total'],
                    'allocated_quantity' => $allocated,
                    'backorder_quantity' => $backorder,
                    'shipped_quantity' => $shipped,
                ]);
            }
            $cart->update([
                'order_id' => $order->order_id,
                'checkout_id' => $request->checkout_id,
                'cart_status' => 'converted',
                'is_active'=>false,
                'cart_token'=>$request->checkout_id,
            ]);
            $this->logEvent($cart, 'order_created', [
                'order_id' => $order->order_id
            ]);
            DB::commit();
            return response()->json([
                'success'=>true,
                'order_id'=>$order->order_id
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
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

    public function orderPlacedDetail(Request $request)
    {
        $corder = Order::with('orderItems')->where('order_id', $request->order_id)
            ->where('customer_id', $request->customer_id)
            ->first();
        if ($corder) {
            foreach ($corder->orderItems as $item) {
                $product = Product::where('product_id', $item->product_id)
                    ->first();
                $item['pimage'] = $product->featured_image ?? 'noimage.png';
            }
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $request->order_id,
                'corder' => $corder,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'order_id' => $request->order_id,
                'corder' => null,
            ]);
        }
    }

    public function createOrderFromCart(Request $request)
    {
        $shopId = $request->shop_id;
        DB::beginTransaction();
        try {
            $acart = Acart::where('shop_id',$shopId)
                ->where('customer_id',$request->customer_id)
                ->where('is_active',true)
                ->latest()
                ->firstOrFail();
            if($acart->order_id){return response()->json(['order_id' => $acart->order_id,]);}

            $items = AcartItem::where('acart_id',$acart->acart_id)->get();
            $subtotal = 0;
            $orderItems = [];

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
