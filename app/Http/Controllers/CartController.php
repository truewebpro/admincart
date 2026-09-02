<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Models\Acart;
use App\Models\AcartCoupon;
use App\Models\AcartEvent;
use App\Models\AcartItem;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopPaymentMethod;
use App\Models\Stock;
use App\Models\Variant;
use App\Models\VivaPayment;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $shopId = $request->shop_id;
        $cartToken = $request->cart_token;
        $customer = auth()->guard('customer')->user();
        if(!$cartToken){
            return response()->json([
                'success' => false,
                'message' => 'cart_token missing'
            ],400);
        }
        $cart = Acart::with('applied_coupons')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->where('cart_token', $cartToken)
            ->first();

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

        if ($customer && $cart->customer_id !== $customer->customer_id) {
            $cart->update([
                'customer_id' => $customer->customer_id,
            ]);
        }
        if($customer){
            $address = CustomerAddress::where('customer_id', $customer->customer_id)
                ->where('is_default', true)
                ->first();
            if($address && $cart->address_id !== $address->address_id){
                $cart->update([
                    'address_id' => $address->address_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'cart' => [
                'acart_id' => $cart->acart_id,
                'cart_token' => $cart->cart_token,
                'items_count' => $cart->items_count,
                'subtotal' => $cart->subtotal,
                'discount_amount' => $cart->discount_amount,
                'shipping_method' => $cart->shipping_method,
                'shipping_amount' => $cart->shipping_amount,
                'shipping_cost' => $cart->shipping_cost,
                'shipping_protection_enabled' => $cart->shipping_protection_enabled,
                'shipping_protection_fee' => $cart->shipping_protection_fee,
                'payment_method' => $cart->payment_method,
                'payment_fee' => $cart->payment_fee,
                'tax_amount' => $cart->tax_amount,
                'notes' => $cart->notes,
                'cart_total' => $cart->cart_total,
                'cart_version' => $cart->cart_version,
                'checkout_id' => $cart->checkout_id,
                'applied_coupons' => $cart->applied_coupons,
            ],
            'items' => $items
        ]);
    }

    public function event(Request $request)
    {
        $shopId = $request->shop_id;
        $customer = auth()->guard('customer')->user();
        $cartToken = $request->cart_token;
        if(!$cartToken){
            return response()->json([
                'success' => false,
                'message' => 'cart_token missing'
            ]);
        }
        $cart = Acart::with('applied_coupons')
            ->where('shop_id', $shopId)
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

            case "order_notes_updated":
                $cart->update(['notes' => $request->notes,]);
                $this->logEvent($cart,'order_notes_updated',[
                    'notes'=>$request->notes,
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

            case "shipping_protection_updated":
                $this->updateShippingProtection($cart, $request);
                break;

            case "payment_selected":
                $this->updatePayment($cart, $request);
                break;

            case "customer_attached":
                $this->attachCustomer($cart, $request);
                break;

            case "start_viva_payment":
                $this->startVivaPayment($cart, $request);
                $this->logEvent($cart,'start_viva_payment',[
                    'orderCode' => $request->checkout_id,
                    'checkout_id' => $request->checkout_id,
                    'order_items' => $request['order_items'],
                    'cart_data' => $request['cart_data'],
                ]);
                break;

            case "start_viva_wallet":
            $this->startVivaPayment($cart, $request);
            $this->logEvent($cart,'start_viva_wallet',[
                'orderCode' => $request->checkout_id,
                'checkout_id' => $request->checkout_id,
                'order_items' =>  $cart->items()->get()->toArray(),
                'cart_data' => $cart,
            ]);
            break;

            case "viva_cancel_payment":
                $this->vivaCancelPayment($cart, $request);
                $this->logEvent($cart,'viva_cancel_payment',[
                    'orderCode' => $request->checkout_id,
                ]);
                break;

            case "viva_payment_confirm":
                $this->logEvent($cart,'viva_payment_confirm',[
                    'orderCode' => $request->checkout_id,
                ]);
                return $this->vivaPaymentConfirm($cart, $request);

            case "start_world_pay":
                $this->startWorldPay($cart, $request);
                $this->logEvent($cart,'start_world_pay',[
                    'transactionReference' => $request->checkout_id,
                    'checkout_id' => $request->checkout_id,
                    'order_items' =>  $cart->items()->get()->toArray(),
                    'cart_data' => $cart,
                ]);
                break;

            case "world_pay_cancel":
                $this->worldPayCancel($cart, $request);
                $this->logEvent($cart,'world_pay_cancel',[
                    'transactionReference' => $request->checkout_id,
                ]);
                break;

            case "pay_bank_transfer":
                return $this->bankCheckout($cart, $request);

            case "pay_by_bank_transfer":
                return $this->payByBankTransfer($cart, $request);

            case "viva_wallet_confirm":
                $this->logEvent($cart,'viva_wallet_confirm',[
                    'orderCode' => $request->checkout_id,
                ]);
                return $this->payByVivaWallet($cart, $request);

            case "world_pay_confirm":
                $this->logEvent($cart,'world_pay_confirm',[
                    'transactionReference' => $request->checkout_id,
                ]);
                return $this->worldPayConfirm($cart, $request);

            case "start_stripe_payment":
                $this->startStripePayment($cart, $request);
                $this->logEvent($cart,'start_stripe_payment',[
                    'checkout_id' => $request->checkout_id,
                    'order_items' =>  $cart->items()->get()->toArray(),
                    'cart_data' => $cart,
                ]);
                break;

            case "stripe_payment_confirm":
                $this->logEvent($cart,'stripe_payment_confirm',[
                    'stripe_status' => $request->stripe_status,
                    'checkout_id' => $request->checkout_id,
                    'payment_intent_id' => $request->payment_intent_id,
                ]);
                return $this->stripePaymentConfirm($cart, $request);

            case "start_cybersource_payment":
                $this->startCyberSourcePayment($cart, $request);
                $this->logEvent($cart,'start_cybersource_payment',[
                    'checkout_id' => $request->checkout_id,
                    'order_items' =>  $cart->items()->get()->toArray(),
                    'cart_data' => $cart,
                ]);
                break;

            case "cybersource_cancel_payment":
                $this->cyberSourceCancelPayment($cart, $request);
                $this->logEvent($cart,'cybersource_cancel_payment',[
                    'checkout_id' => $request->checkout_id,
                    'reason' => $request->reason,
                ]);
                break;

            case "cybersource_payment_confirm":
                $this->logEvent($cart,'cybersource_payment_confirm',[
                    'checkout_id' => $request->checkout_id,
                    'transaction_id' => $request->transaction_id,
                    'decision' => $request->decision,
                ]);
                return $this->cyberSourcePaymentConfirm($cart, $request);

            default:
                return response()->json([
                    'success'=>false,
                    'message'=>'Invalid event type',
                    'cart' => $cart,
                ],400);
        }

        if ($customer && $cart->customer_id !== $customer->customer_id) {
            $cart->update([
                'customer_id' => $customer->customer_id,
            ]);
        }
        if($customer){
            $address = CustomerAddress::where('customer_id', $customer->customer_id)
                ->where('is_default', true)
                ->first();
            if($address && $cart->address_id !== $address->address_id){
                $cart->update([
                    'address_id' => $address->address_id,
                ]);
            }
        }

        $this->recalculateCart($cart);

        $cartItems = $cart->items()->with([
            'product:product_id,title,handle,featured_image',
            'variant:variant_id,sku,variant_image,option_values'
        ])->get();

        return response()->json([
            'success' => true,
            'message' => $request->type." ". "success",
            'cart' => $cart,
            'items' => $cartItems
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
//        $item->options_json = $request->options ?? [];
//        $item->options_json = $variant->option_values ?? [];
        $item->options_json = collect($variant->option_values ?? [])->map(function ($value, $key) {
            return [
                'name' => $key,
                'value' => $value,
            ];
        })->values()->toArray();

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
        $stock = $this->getVariantStock($request->variant_id);
        if ($stock !== null && $request->newQty > $stock) {
            abort(422, 'Not enough stock');
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

    private function updateShippingProtection($cart, $request)
    {
        $cart->update([
            'shipping_protection_enabled' => $request->shipping_protection_enabled,
            'shipping_protection_fee'   => $request->shipping_protection_fee,
            'cart_status'     => 'shipping_protection_updated'
        ]);

        $this->logEvent($cart, 'shipping_protection_updated', [
            'shipping_protection_enabled' => $request->shipping_protection_enabled,
            'shipping_protection_fee'   => $request->shipping_protection_fee,
        ]);
    }

    private function updatePayment($cart, $request)
    {
        $cart->update([
            'payment_method' => $request->payment_method,
            'cart_status'    => 'payment_selected'
        ]);

        $this->logEvent($cart, 'payment_selected', [
            'method' => $request->payment_method,
        ]);
    }

    private function attachCustomer($cart, $request)
    {
        $updates = [];
        if ($request->filled('customer_id') && $cart->customer_id != $request->customer_id) {
            $updates['customer_id'] = $request->customer_id;
        }

        if ($request->filled('address_id') && $cart->address_id != $request->address_id) {
            $updates['address_id'] = $request->address_id;
        }

        if (!empty($updates)) {
            $updates['cart_status'] = 'customer_attached';
            $cart->update($updates);

            $this->logEvent($cart, 'customer_attached', [
                'customer_id' => $updates['customer_id'] ?? $cart->customer_id,
                'address_id' => $updates['address_id'] ?? $cart->address_id,
            ]);
        }
    }

    private function startVivaPayment($cart, $request)
    {
        $cart->update([
            'checkout_id' => $request->checkout_id,
            'cart_status' => 'start_viva_payment',
        ]);
    }

    private function startWorldPay($cart, $request)
    {
        $cart->update([
            'checkout_id' => $request->checkout_id,
            'cart_status' => 'start_world_pay',
        ]);
    }

    private function startStripePayment($cart, $request)
    {
        $cart->update([
            'checkout_id' => $request->checkout_id,
            'cart_status' => 'start_stripe_payment',
        ]);
    }

    private function vivaCancelPayment($cart, $request)
    {
        $cart->update([
            'cart_status' => 'viva_cancel_payment'
        ]);
    }

    private function worldPayCancel($cart, $request)
    {
        $cart->update([
            'cart_status' => 'world_pay_cancel'
        ]);
    }

    private function startCyberSourcePayment($cart, $request)
    {
        $cart->update([
            'checkout_id' => $request->checkout_id,
            'cart_status' => 'start_cybersource_payment',
        ]);
    }

    private function cyberSourceCancelPayment($cart, $request)
    {
        $cart->update([
            'cart_status' => 'cybersource_cancel_payment'
        ]);
    }

    private function vivaPaymentConfirm($cart, $request)
    {
        if($cart->order_id){return response()->json(['success'=>true, 'order_id'=>$cart->order_id]);}
        $acartEvent = AcartEvent::where('acart_id',$cart->acart_id)
            ->where('event_type','=','start_viva_payment')
            ->where('event_data->orderCode','=',$request->checkout_id)
            ->first();
        if($acartEvent){
            DB::beginTransaction();
            try {
                $eventData = $acartEvent->event_data;
                $items = $eventData['order_items'];
                $cartData = $eventData['cart_data'];
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
                    'payment_status' => 'paid',
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
                    'shipping_phone' => $cartData['shipping_phone'],
                    'shipping_address_line1' => $cartData['shipping_address_line1'],
                    'shipping_address_line2' => $cartData['shipping_address_line2'],
                    'shipping_city' => $cartData['shipping_city'],
                    'shipping_postcode' => $cartData['shipping_postcode'],
                    'shipping_country' => $cartData['shipping_country'],
                    'notes' => $cartData['notes'],
                    'checkout_id' => $request->checkout_id,
                    'placed_at' => now(),
                    'shipping_protection_fee' => $cartData['shipping_protection_fee'] ?? 0,
                    'payment_fee' => $cartData['payment_fee'] ?? 0,
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
    }

    private function stripePaymentConfirm($cart,$request)
    {
        if($cart->order_id){return response()->json(['success'=>true, 'order_id'=>$cart->order_id]);}
        $acartEvent = AcartEvent::where('acart_id',$cart->acart_id)
            ->where('event_type','=','start_stripe_payment')
            ->where('event_data->checkout_id','=',$request->checkout_id)
            ->first();
        if($acartEvent){
            DB::beginTransaction();
            try {
                $eventData = $acartEvent->event_data;
                $items = $eventData['order_items'];
                $cartData = $eventData['cart_data'];
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
                    'payment_status' => 'paid',
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
                    'shipping_phone' => $cartData['shipping_phone'],
                    'shipping_address_line1' => $cartData['shipping_address_line1'],
                    'shipping_address_line2' => $cartData['shipping_address_line2'],
                    'shipping_city' => $cartData['shipping_city'],
                    'shipping_postcode' => $cartData['shipping_postcode'],
                    'shipping_country' => $cartData['shipping_country'],
                    'notes' => $cartData['notes'],
                    'checkout_id' => $request->checkout_id,
                    'placed_at' => now(),
                    'shipping_protection_fee' => $cartData['shipping_protection_fee'] ?? 0,
                    'payment_fee' => $cartData['payment_fee'] ?? 0,
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
    }

    public function createMissingOrder(Request $request)
    {
        $acartEvent = AcartEvent::where('acart_event_id', $request->acart_event_id)->first();
        if($acartEvent){
            $cart = Acart::where('acart_id', $acartEvent->acart_id)->firstOrFail();
            DB::beginTransaction();
            try {
                $eventData = $acartEvent->event_data;
                $items = $eventData['order_items'];
                $cartData = $eventData['cart_data'];
                if (empty($items)) {return response()->json(['success' => false,'message' => 'Cart is empty'], 400);}
                $prefix = Shop::where('shop_id', $cart->shop_id)->value('order_prefix') ?? "#";
                $lastOrder = Order::withTrashed()->where('shop_id', $cart->shop_id)->orderByDesc('order_id')->first();
                $lastOrderNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder->order_number)): 1000;
                $orderNumber = $prefix . ($lastOrderNumber + 1);
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'shop_id' => $cart->shop_id,
                    'customer_id' => $cartData['customer_id'],
                    'address_id' => $cartData['address_id'],
                    'order_status' => 'pending',
                    'label_status' => 'no_label',
                    'payment_method' => $cartData['payment_method'],
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'unfulfilled',
                    'shipping_method' => $cartData['shipping_method'],
                    'shipping_cost' => $cartData['shipping_cost'],
                    'coupon_id' => $cartData['coupon_id'],
                    'coupon_code' => $cartData['coupon_code'],
                    'discount_amount' => $cartData['discount_amount'],
                    'subtotal' => $cartData['subtotal'],
                    'order_total' => $cartData['order_total'],
                    'tax_amount' => $cartData['tax_amount'],
                    'currency_code' => $cartData['currency_code'],
                    'is_guest_order' => $cartData['is_guest_order'],
                    'shipping_name' => $cartData['shipping_name'],
                    'shipping_phone' => $cartData['shipping_phone'],
                    'shipping_address_line1' => $cartData['shipping_address_line1'],
                    'shipping_address_line2' => $cartData['shipping_address_line2'],
                    'shipping_city' => $cartData['shipping_city'],
                    'shipping_postcode' => $cartData['shipping_postcode'],
                    'shipping_country' => $cartData['shipping_country'],
                    'notes' => $cartData['notes'],
                    'checkout_id' => $eventData['checkout_id'],
                    'placed_at' => now(),
                    'shipping_protection_fee' => $cartData['shipping_protection_fee'] ?? 0,
                    'payment_fee' => $cartData['payment_fee'] ?? 0,
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
                    'checkout_id' => $eventData['checkout_id'],
                    'cart_status' => 'converted',
                    'is_active'=>false,
                    'cart_token'=>$eventData['checkout_id'],
                ]);
                $this->logEvent($cart, 'order_created', [
                    'order_id' => $order->order_id
                ]);
                DB::commit();
                return response()->json([
                    'success'=>true,
                    'order_id'=> $order->order_id,
                    'order'=> $order
                ]);

            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }

    private function payByBankTransfer($cart,$request){
        if($cart->order_id){
            return response()->json([
                'success'=>true,
                'order_id'=>$cart->order_id
            ]);
        }
        DB::beginTransaction();
        try {
            $order = $this->createOrderDetails(
                $cart,
                $request->checkout_id,
               'pending'
            );
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
            broadcast(new OrderCreated($order));
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

    private function payByVivaWallet($cart, $request)
    {
        if($cart->order_id){
            return response()->json([
                'success'=>true,
                'order_id'=>$cart->order_id
            ]);
        }
        DB::beginTransaction();
        try {
            $order = $this->createOrderDetails(
                $cart,
                $request->checkout_id,
                'paid',
            );
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
            broadcast(new OrderCreated($order));
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

    private function worldPayConfirm($cart, $request)
    {
        if($cart->order_id){
            return response()->json([
                'success'=>true,
                'order_id'=>$cart->order_id
            ]);
        }
        DB::beginTransaction();
        try {
            $order = $this->createOrderDetails(
                $cart,
                $request->checkout_id,
                'paid',
            );
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
            broadcast(new OrderCreated($order));
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

    private function cyberSourcePaymentConfirm($cart, $request)
    {
        if($cart->order_id){
            return response()->json([
                'success'=>true,
                'order_id'=>$cart->order_id
            ]);
        }

        DB::beginTransaction();
        try {
            $order = $this->createOrderDetails(
                $cart,
                $request->checkout_id,
                'paid',
            );

            $cart->update([
                'order_id' => $order->order_id,
                'checkout_id' => $request->checkout_id,
                'cart_status' => 'converted',
                'is_active'=>false,
                'cart_token'=>$request->checkout_id,
            ]);
            $this->logEvent($cart, 'order_created', [
                'order_id' => $order->order_id,
                'checkout_id' => $request->checkout_id,
            ]);
            DB::commit();
            broadcast(new OrderCreated($order));
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

    private function generateOrderNumber($cart)
    {
        $prefix = Shop::where('shop_id', $cart->shop_id)->value('order_prefix') ?? "#";
        $lastOrder = Order::withTrashed()->where('shop_id', $cart->shop_id)->orderByDesc('order_id')->first();
        $lastOrderNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder->order_number)): 1000;
        $orderNumber = $prefix . ($lastOrderNumber + 1);
        return $orderNumber;
    }

    private function createOrderDetails($cart, string $checkoutId, string $paymentStatus = 'pending', string $orderStatus = 'pending'):Order
    {
        $orderNumber = $this->generateOrderNumber($cart);
        $address = CustomerAddress::where('address_id', $cart->address_id)->first();
        $order = Order::create([
            'order_number' => $orderNumber,
            'shop_id' => $cart->shop_id,
            'customer_id' => $cart->customer_id,
            'address_id' => $cart->address_id,
            'order_status' => $orderStatus,
            'label_status' => 'no_label',
            'payment_method' => $cart->payment_method,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => 'unfulfilled',
            'shipping_method' => $cart->shipping_method,
            'shipping_cost' => $cart->shipping_cost,
            'coupon_id' => $cart->coupon_id,
            'coupon_code' => $cart->coupon_code,
            'discount_amount' => $cart->discount_amount,
            'coupon_discount' => $cart->coupon_discount,
            'subtotal' => $cart->subtotal,
            'order_total' => $cart->cart_total,
            'tax_amount' => $cart->tax_amount,
            'currency_code' => $cart->currency,
            'is_guest_order' => false,
            'shipping_name' => $address->fname ." " . $address->lname,
            'shipping_phone' =>  $address->phone,
            'shipping_address_line1' =>  $address->address_line1,
            'shipping_address_line2' =>  $address->address_line2,
            'shipping_city' =>  $address->city,
            'shipping_postcode' =>  $address->postcode,
            'shipping_country' =>  $address->country ?? "United Kingdom",
            'notes' => $cart->notes,
            'checkout_id' => $checkoutId,
            'placed_at' => now(),
            'payment_fee' => $cart->payment_fee ?? 0,
            'shipping_protection_fee' => $cart->shipping_protection_fee ?? 0,
        ]);
        foreach ($cart->items as $item) {
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
                'options' => $item['options_json'],
                'price' => $item['price'],
                'quantity' => $ordered,
                'total' => $item['line_total'],
                'allocated_quantity' => $allocated,
                'backorder_quantity' => $backorder,
                'shipped_quantity' => $shipped,
            ]);
        }
        return $order;
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
                'shipping_phone' => $cartData['shipping_phone'],
                'shipping_address_line1' => $cartData['shipping_address_line1'],
                'shipping_address_line2' => $cartData['shipping_address_line2'],
                'shipping_city' => $cartData['shipping_city'],
                'shipping_postcode' => $cartData['shipping_postcode'],
                'shipping_country' => $cartData['shipping_country'],
                'notes' => $cartData['notes'],
                'checkout_id' => $request->checkout_id,
                'placed_at' => now(),
                'shipping_protection_fee' => $cartData['shipping_protection_fee'] ?? 0,
                'payment_fee' => $cartData['payment_fee'] ?? 0,
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
            broadcast(new OrderCreated($order));
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
        $subtotal = AcartItem::where('acart_id',$cart->acart_id)->sum('line_total');

        $itemCount = AcartItem::where('acart_id',$cart->acart_id)->sum('quantity');

        $paymentFee = $this->calculatePaymentFee($cart, $subtotal);

        $settings = Setting::where('shop_id',$cart->shop_id)->first();
        $vatIncluded = $settings->vat_included ?? true;

        $preTaxTotal =
            (float)$subtotal
            + (float)$cart->shipping_cost
            - (float)$cart->discount_amount
            + (float)$paymentFee
            + (float)$cart->shipping_protection_fee;

        if ($vatIncluded) {
            $taxAmount = $preTaxTotal - ($preTaxTotal / 1.20);
            $cartTotal = $preTaxTotal;
        } else {
            $taxAmount = $preTaxTotal * 0.20;
            $cartTotal = $preTaxTotal + $taxAmount;
        }

        $cart->update([
            'items_count' => $itemCount,
            'subtotal' => $subtotal,
            'payment_fee' => $paymentFee,
            'tax_amount' => round($taxAmount, 2),
            'cart_total' => round($cartTotal, 2),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(48),
            'cart_version' => $cart->cart_version + 1,
        ]);
    }

    private function calculatePaymentFee($cart, $subtotal)
    {
        if(!$cart->payment_method) {
            return 0;
        }

        $paymentMethod = ShopPaymentMethod::where('shop_id',$cart->shop_id)
            ->where('payment_name',$cart->payment_method)
            ->select('fee_type','handling_fee')
            ->first();
        if(!$paymentMethod) {
            return 0;
        }
        $feeValue = (float) $paymentMethod->handling_fee;
        if ($paymentMethod->fee_type === 'fixed') {
            return round($feeValue, 2);
        }
        return round(
            (
                (
                    (float)$subtotal
                    - (float)$cart->discount_amount
                    + (float)$cart->shipping_cost
                    + (float)$cart->shipping_protection_fee
                ) * $feeValue
            ) / 100,
            2
        );
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

    public function getOrderDetail(Request $request)
    {
        $user = auth()->guard('customer')->user();
        $corder = Order::with('items')
            ->where('order_id', $request->order_id)
            ->where('customer_id', $user->customer_id)
            ->first();
        $productImages = Product::whereIn('product_id', $corder->items->pluck('product_id'))
            ->pluck('featured_image', 'product_id');
        if ($corder) {
            foreach ($corder->items as $item) {
                $item['pimage'] = $productImages[$item->product_id] ?? 'noimage.png';
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

    public function getAvailableCoupons(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $acart = Acart::where('cart_token', $request->cart_token)
            ->where('shop_id', $shopId)
            ->first();

        if (!$acart) {
            return response()->json(['success' => false]);
        }

        $result = app(PricingEngine::class)->calculate($acart);

        $cartItems = $result['items'];
        $subtotal = $result['subtotal'];

        $coupons = Coupon::with(['products','cats'])
            ->where('shop_id', $shopId)
            ->orderBy('priority','asc')
            ->get()
            ->filter(function($coupon) use($subtotal,$cartItems){
                // min order check
                if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
                    return false;
                }

                // scope check
                if ($coupon->applies_to === 'products') {
                    $ids = $coupon->products->pluck('product_id')->toArray();

                    return collect($cartItems)->contains(fn($i) =>
                    in_array($i['product_id'], $ids)
                    );
                }

                if ($coupon->applies_to === 'cats') {
                    $catIds = $coupon->cats->pluck('cat_id')->toArray();

                    return collect($cartItems)->contains(fn($i) =>
                        count(array_intersect($i['cat_ids'], $catIds)) > 0
                    );
                }

                return true;
            })
            ->values()
            ->map(function($c){
                return [
                    'coupon_id' => $c->coupon_id,
                    'code' => $c->code,
                    'type' => $c->type,
                    'value' => $c->value,
                    'is_stackable' => $c->is_stackable,
                    'label' => $c->type === 'fixed'
                        ? "£{$c->value} off"
                        : "{$c->value}% off"
                ];
            });
        return response()->json([
            'success' => true,
            'coupons' => $coupons
        ]);
    }

    public function applyCouponToCart(Request $request,$shopname)
    {
        $shopId = $request->shop_id;
        $request->validate([
            'cart_token' => 'required',
            'code' => 'required'
        ]);

        $code = strtoupper(preg_replace('/\s+/', '', $request->code));

        $acart = Acart::where('cart_token', $request->cart_token)
            ->where('shop_id', $shopId)
            ->firstOrFail();

        $coupon = Coupon::where('shop_id', $shopId)
            ->where('code', $code)
            ->active()
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon'
            ]);
        }

        // ❗ handle stackable
        if (!$coupon->is_stackable) {
            AcartCoupon::where('acart_id', $acart->acart_id)->delete();
        }

        // prevent duplicate
        AcartCoupon::updateOrCreate(
            [
                'acart_id' => $acart->acart_id,
                'coupon_code' => $code
            ],
            [
                'coupon_id' => $coupon->coupon_id,
                'shop_id' => $shopId,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'priority' => $coupon->priority
            ]
        );

        // 🔥 recalc cart
        $this->recalculateCouponCart($acart);

        return response()->json([
            'success' => true,
            'message' => 'Coupon Applied'
        ]);
    }

    private function recalculateCouponCart($acart)
    {
        $codes = AcartCoupon::where('acart_id', $acart->acart_id)
            ->pluck('coupon_code')
            ->toArray();

        $result = app(PricingEngine::class)
            ->calculate($acart, $codes);

        $acart->update([
            'discount_amount' => $result['total_discount'],
            'cart_total' => $result['final_total']
        ]);

        // update breakdown
        foreach ($result['coupon_breakdown'] as $c) {
            AcartCoupon::where('acart_id', $acart->acart_id)
                ->where('coupon_code', $c['code'])
                ->update([
                    'discount_amount' => $c['discount']
                ]);
        }
    }

    public function removeCoupon(Request $request)
    {
        $acart = Acart::where('cart_token', $request->cart_token)->first();

        AcartCoupon::where('acart_id', $acart->acart_id)
            ->where('coupon_code', $request->code)
            ->delete();

        $this->recalculateCart($acart);

        return response()->json(['success' => true]);
    }
}
