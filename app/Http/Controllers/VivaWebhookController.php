<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Models\Acart;
use App\Models\AcartEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\VivaPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VivaWebhookController extends Controller
{
//    public function __construct()
//    {
//        $this->middleware('auth:api', ['except' => ['webhookTransactionCreated']]);
//    }

    public function handleWebhook(Request $request,$shopname)
    {
        Log::info('✅ Viva Webhook Received:', $request->all());
        $rdata = $request->all();
        if (!isset($rdata['EventData'])) {
            Log::error('Webhook missing EventData', $rdata);
            return response()->json(['error'=>'Invalid payload'],400);
        }
        $payload = $rdata['EventData'];
        $shopId = $request->shop_id;
        $vpay = VivaPayment::updateOrCreate(
            [
                'order_code' => $payload['OrderCode'],
                'shop_id' => $shopId,
            ],
            [
                'order_code' => $payload['OrderCode'] ?? null,
                'transaction_id' => $payload['TransactionId'] ?? null,
                'status_id' => $payload['StatusId'],
                'amount' => $payload['Amount'],
                'fullname' => $payload['FullName'] ?? null,
                'email' => $payload['Email'],
                'shop_id' => $shopId,
                'payload' => $payload,
            ]);
        $cart =  Acart::where('shop_id', $shopId)
            ->where('checkout_id', $vpay->order_code)
            ->where('is_active', true)
            ->first();
        if($cart){
            if($cart->order_id){
                return response()->json(['success'=>true, 'order_id'=>$cart->order_id]);
            }
        }

//        $acartEvent = AcartEvent::where('acart_id',$cart->acart_id)
//            ->where('event_type','=','start_viva_payment')
//            ->where('event_data->orderCode','=',$vpay->order_code)
//            ->first();
//        if($acartEvent){
//            try {
//                DB::beginTransaction();
//                $eventData = $acartEvent->event_data;
//                $items = $eventData['order_items'];
//                $cartData = $eventData['cart_data'];
//                if (empty($items)) {
//                    return response()->json([
//                        'success' => false,
//                        'message' => 'Cart is empty'
//                    ], 400);
//                }
//                $prefix = Shop::where('shop_id', $cart->shop_id)->value('order_prefix') ?? "#";
//                $lastOrder = Order::withTrashed()->where('shop_id', $cart->shop_id)->orderByDesc('order_id')->first();
//                $lastOrderNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder->order_number)): 1000;
//                $orderNumber = $prefix . ($lastOrderNumber + 1);
//                $order = Order::create([
//                    'order_number' => $orderNumber,
//                    'shop_id' => $cart->shop_id,
//                    'customer_id' => $cart->customer_id,
//                    'address_id' => $cartData['address_id'],
//                    'order_status' => 'pending',
//                    'label_status' => 'no_label',
//                    'payment_method' => $cartData['payment_method'],
//                    'payment_status' => 'paid',
//                    'fulfillment_status' => 'unfulfilled',
//                    'shipping_method' => $cartData['shipping_method'],
//                    'shipping_cost' => $cartData['shipping_cost'],
//                    'coupon_id' => $cartData['coupon_id'],
//                    'coupon_code' => $cartData['coupon_code'],
//                    'discount_amount' => $cartData['discount_amount'],
//                    'subtotal' => $cartData['subtotal'],
//                    'order_total' => $cartData['order_total'],
//                    'tax_amount' => $cartData['tax_amount'],
//                    'currency_code' => $cart->currency,
//                    'is_guest_order' => $cartData['is_guest_order'],
//                    'shipping_name' => $cartData['shipping_name'],
//                    'shipping_phone' => $cartData['shipping_phone'],
//                    'shipping_address_line1' => $cartData['shipping_address_line1'],
//                    'shipping_address_line2' => $cartData['shipping_address_line2'],
//                    'shipping_city' => $cartData['shipping_city'],
//                    'shipping_postcode' => $cartData['shipping_postcode'],
//                    'shipping_country' => $cartData['shipping_country'],
//                    'notes' => $cartData['notes'],
//                    'checkout_id' =>$cart->checkout_id,
//                    'placed_at' => now(),
//                    'shipping_protection_fee' => $cartData['shipping_protection_fee'] ?? 0,
//                    'payment_fee' => $cartData['payment_fee'] ?? 0,
//                ]);
//                foreach ($items as $item) {
//                    $stock = Stock::where('variant_id', $item['variant_id'])
//                        ->where('shop_id', $cart->shop_id)
//                        ->lockForUpdate()
//                        ->first();
//                    $available = $stock ? $stock->quantity : 0;
//                    $ordered = $item['quantity'];
//                    $allocated = min($available, $ordered);
//                    $backorder = $ordered - $allocated;
//                    $shipped = 0;
//                    // Deduct only allocated
//                    if ($stock && $allocated > 0) {
//                        $stock->decrement('quantity', $allocated);
//                    }
//                    OrderItem::create([
//                        'order_id' => $order->order_id,
//                        'product_id' => $item['product_id'],
//                        'variant_id' => $item['variant_id'],
//                        'title' => $item['title'],
//                        'options' => $item['options'],
//                        'price' => $item['price'],
//                        'quantity' => $ordered,
//                        'total' => $item['total'],
//                        'allocated_quantity' => $allocated,
//                        'backorder_quantity' => $backorder,
//                        'shipped_quantity' => $shipped,
//                    ]);
//                }
//                $cart->update([
//                    'order_id' => $order->order_id,
//                    'checkout_id' => $cart->checkout_id,
//                    'cart_status' => 'converted',
//                    'is_active'=>false,
//                    'cart_token'=>$cart->checkout_id,
//                ]);
//                $this->logEvent($cart, 'order_created', [
//                    'order_id' => $order->order_id
//                ]);
//                DB::commit();
//                return response()->json([
//                    'success'=>true,
//                    'order_id'=>$order->order_id
//                ]);
//            }  catch (\Exception $e) {
//                DB::rollBack();
//                Log::error('Webhook order creation failed', [
//                    'error' => $e->getMessage()
//                ]);
//                return response()->json([
//                    'error' => 'Order failed',
//                    'details' => $e->getMessage()
//                ], 500);
//            }
//        }

        try {
            DB::beginTransaction();

//            $cart = Cart::where('checkout_id', $payload['OrderCode'])
//                ->lockForUpdate()
//                ->with('cartItems')
//                ->first();
//            if (!$cart) {
//                DB::rollBack();
//                Log::warning('Cart not found for OrderCode: ' . $payload['OrderCode']);
//                return response()->json(['no_cart' => true]);
//            }
//            if ($cart->order_id) {
//                DB::rollBack();
//                return response()->json(['already_processed' => true]);
//            }
            if (Order::withTrashed()->where('checkout_id', $payload['OrderCode'])->exists()) {
                DB::rollBack();
                return response()->json(['duplicate' => true]);
            }
//            $prefix = Shop::where('shop_id', $cart->shop_id)
//                ->value('order_prefix') ?? '#';
//            $lastOrder = Order::withTrashed()->where('shop_id', $cart->shop_id)
//                ->lockForUpdate()
//                ->orderByDesc('order_id')
//                ->first();
//            $lastOrderNumber = $lastOrder
//                ? intval(preg_replace('/[^0-9]/', '', $lastOrder->order_number))
//                : 1000;
//            $orderNumber = $prefix . ($lastOrderNumber + 1);
//            $placedAt = now();
//            $order = Order::create([
//                'order_number' => $orderNumber,
//                'payment_status' => 'paid',
//                'order_status' => 'processing',
//                'shop_id' => $cart->shop_id,
//                'customer_id' => $cart->customer_id,
//                'address_id' => $cart->address_id,
//                'is_guest_order' => $cart->is_guest_order,
//                'shipping_method' => $cart->shipping_method,
//                'payment_method' => $cart->payment_method,
//                'shipping_name' => $cart->shipping_name,
//                'shipping_phone' => $cart->shipping_phone,
//                'shipping_address_line1' => $cart->shipping_address_line1,
//                'shipping_address_line2' => $cart->shipping_address_line2,
//                'shipping_city' => $cart->shipping_city,
//                'shipping_postcode' => $cart->shipping_postcode,
//                'shipping_country' => $cart->shipping_country,
//                'shipping_cost' => $cart->shipping_cost,
//                'discount_amount' => $cart->discount_amount,
//                'subtotal' => $cart->subtotal,
//                'order_total' => $cart->order_total,
//                'tax_amount' => $cart->tax_amount,
//                'notes' => $cart->notes,
//                'checkout_id' => $cart['checkout_id'],
//                'placed_at' => $placedAt,
//            ]);
//            foreach ($cart->cartItems as $cartItem) {
//                $stock = Stock::where('variant_id', $cartItem->variant_id)
//                    ->where('shop_id', $cart->shop_id)
//                    ->lockForUpdate()
//                    ->first();
//                $available = $stock ? $stock->quantity : 0;
//                $ordered = $cartItem->quantity;
//                $allocated = min($available, $ordered);
//                $backorder = $ordered - $allocated;
//                $shipped = 0;
//                // Deduct only allocated
//                if ($stock && $allocated > 0) {
//                    $stock->decrement('quantity', $allocated);
//                }
//                OrderItem::create([
//                    'order_id' => $order->order_id,
//                    'product_id' => $cartItem->product_id,
//                    'variant_id' => $cartItem->variant_id,
//                    'title' => $cartItem->title,
//                    'options' => $cartItem->options,
//                    'quantity' => $ordered,
//                    'price' => $cartItem->price,
//                    'total' => $cartItem->total,
//                    'allocated_quantity' => $allocated,
//                    'backorder_quantity' => $backorder,
//                    'shipped_quantity' => $shipped,
//                ]);
//            }
//            $cart->update([
//                'order_id' => $order->order_id,
//                'is_active' => 0,
//                'payment_status' => 'paid',
//                'order_status' => 'processing',
//                'placed_at' => $placedAt,
//            ]);
            $order = Order::withTrashed()->where('checkout_id', $payload['OrderCode'])->first();
            DB::commit();
            broadcast(new OrderUpdated($order));
            return response()->json(['success'=>true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook main order creation failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Order failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getConfigToken(Request $request)
    {
        try {
//            $merchantId = 'ouq0kfrqank98ufuvgjjpnq5nnt6ub68ogj3bvsc1t8r4.apps.vivapayments.com';
//            $apiKey = '737wg4ph02QWh9EOjvx92QoNr163wM';
//            $merchantId = 'd7c8be6e-b3ba-4a52-aa42-3232b57bb886';
//            $apiKey = 'Emqyzp4F7T78JtW9JQy805R';
            if($request->shopname === "vapecraze"){
                $merchantId = 'd7c8be6e-b3ba-4a52-aa42-3232b57bb886';
                $apiKey = 'Emqyzp4F7T78JtW9JQy805R';
            }
            if($request->shopname === "vapeportwholesale"){
                $merchantId = 'c104ea18-8667-42b8-86d3-cdfe6e56760b';
                $apiKey = 'hxX009b2H2cf1B2G9mB165Ao87aR9C';
            }

            if($request->shopname === "nextgenvapes"){
                $merchantId = 'ddc3db2c-c942-4e62-a533-6af8089e0fce';
                $apiKey = 'X58A4szkG74q05Ob3S4B8GtMQ38whK';
            }
            if($request->shopname === "vapestonewholesale"){
                $merchantId = 'f058087e-8de7-44b9-9b48-312969b64532';
                $apiKey = 'up9kGaHV14VchY7agO0z3Lk93Fo2xU';
            }
            
            $vportmerchantId = 'c104ea18-8667-42b8-86d3-cdfe6e56760b';
            $vportapiKey = 'hxX009b2H2cf1B2G9mB165Ao87aR9C';
            $tokenUrl = 'https://www.vivapayments.com';
            $testUrl = 'https://demo.vivapayments.com';

            $credentials = base64_encode($merchantId . ':' . $apiKey);

            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$credentials,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(30)->get($tokenUrl.'/api/messages/config/token');

            if (!$response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyWebhook(Request $request)
    {
//        $clientId = 'ouq0kfrqank98ufuvgjjpnq5nnt6ub68ogj3bvsc1t8r4.apps.vivapayments.com';
//        $secretKey = '737wg4ph02QWh9EOjvx92QoNr163wM';
        try {
            if($request->shopname === "vapecraze"){
                $merchantId = 'd7c8be6e-b3ba-4a52-aa42-3232b57bb886';
                $apiKey = 'fHBpJfoEmqyzp4F7T78JtW9JQy805R';
            }

            if($request->shopname === "vapeportwholesale"){
                $merchantId = 'c104ea18-8667-42b8-86d3-cdfe6e56760b';
                $apiKey = 'hxX009b2H2cf1B2G9mB165Ao87aR9C';
            }

            if($request->shopname === "nextgenvapes"){
                $merchantId = 'ddc3db2c-c942-4e62-a533-6af8089e0fce';
                $apiKey = 'X58A4szkG74q05Ob3S4B8GtMQ38whK';
            }

            if($request->shopname === "vapestonewholesale"){
                $merchantId = 'f058087e-8de7-44b9-9b48-312969b64532';
                $apiKey = 'up9kGaHV14VchY7agO0z3Lk93Fo2xU';
            }

            $tokenUrl = 'https://www.vivapayments.com/api/messages/config/token';

            $credentials = base64_encode($merchantId.':'.$apiKey);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Accept' => 'application/json',
            ])->get($tokenUrl);

            if ($response->successful()) {
                return response()->json($response->json());
            }
            return response()->json([
                'success' => false,
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
