<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Mail\OrderPlacedMail;
use App\Models\BusinessShop;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Preference;
use App\Models\Sendcloud;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function allOrders(Request $request)
    {
        $shopId = session('shop_id');
        $search = $request->search;
        $status = $request->status;
        $query = Order::withTrashed()
            ->with('trackingEvents')
            ->withCount('orderItems')
            ->where('shop_id','=',$shopId);
        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('order_number', 'LIKE', "%{$term}%")
                            ->orWhere('shipping_name', 'LIKE', "%{$term}%");
                    });
                }
            });
        }
        $allowedSorts = [
            'order_number' => 'order_number',
            'placed_at' => 'placed_at',
            'shipping_name' => 'shipping_name',
            'order_total' => 'order_total',
            'payment_status' => 'payment_status',
            'fulfillment_status' => 'fulfillment_status',
            'order_items_count' => 'order_items_count',
        ];
        if ($status && $status !== 'all') {
            if ($status === 'archived') {
                $query->whereNotNull('deleted_at');
            } else {
                $query->whereNull('deleted_at')
                    ->where('order_status', $status);
            }
        } else {
            $query->whereNull('deleted_at');
        }
        $sortBy = $allowedSorts[$request->sort_by] ?? 'placed_at';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->per_page;
        if ($perPage === -1) {
            $perPage = min($query->count(), 500);
        } else {
            $perPage = $perPage > 0
                ? min($perPage, 500)
                : 50;
        }
        $orders = $query->paginate($perPage);

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function orderStats()
    {
        $shopId = session('shop_id');
        $stats = Order::where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->selectRaw("
            SUM(CASE WHEN fulfillment_status = 'unfulfilled' THEN 1 ELSE 0 END) as unfulfilled,
            SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing
        ")
            ->first();

        return response()->json([
            'unfulfilled' => (int) $stats->unfulfilled,
            'pending' => (int) $stats->pending,
            'processing' => (int) $stats->processing,
        ]);
    }

    public function getOrderById($order_id)
    {
        $shopId = session('shop_id');
        $order = Order::withTrashed()->with('orderItems.product','orderItems.variant','customer')
            ->withCount('orderItems')
            ->where('order_id','=',$order_id)
            ->where('shop_id','=',$shopId)
            ->first();
        $previous = Order::where('order_id', '<', $order_id)
            ->where('shop_id','=',$shopId)
            ->orderBy('order_id', 'desc')
            ->first();

        $next = Order::where('order_id', '>', $order_id)
            ->where('shop_id','=',$shopId)
            ->orderBy('order_id', 'asc')
            ->first();

        $latest = Order::latest()->where('shop_id','=',$shopId)->value('order_id');
        if($order){
            $logs = OrderLog::where('order_id','=',$order_id)
                ->latest()->get();
            return response()->json([
                'success' => true,
                'order' => $order,
                'logs' => $logs,
                'previous_id' => $previous?->order_id,
                'next_id' => $next?->order_id,
                'latest_id' => $latest,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'order' => null,
                'logs' => null
            ]);
        }
    }

    public function updateAdminOrder(Request $request)
    {
        $order = Order::withTrashed()->with('orderItems')->find($request['order_id']);
        if($order){
            if (!$this->canTransition($order, $request->mname,$request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action for current order state'
                ], 400);
            }
            if ($request->mname === 'update_shipping_address') {

                $order->update([
                    'shipping_name' => $request->shipping_name,
                    'shipping_address_line1' => $request->shipping_address_line1,
                    'shipping_address_line2' => $request->shipping_address_line2,
                    'shipping_city' => $request->shipping_city,
                    'shipping_postcode' => $request->shipping_postcode,
                    'shipping_country' => $request->shipping_country,
                    'shipping_phone' => $request->shipping_phone,
                ]);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'address_updated',
                    'description' => 'Shipping address updated',
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Address updated successfully',
                ]);
            }
            if($request->mname === 'update_fulfillment_status'){
                $from = $order->fulfillment_status;
                $to = $request->fulfillment_status;
                $order->update([
                    'fulfillment_status' => $to
                ]);
                $this->syncOrderStatus($order);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => "Fulfillment status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);
                return response()->json([
                    'success' => true,
                    'message' => "Fulfillment updated to {$to}",
                ]);
            }
            if ($request->mname === 'update_order_status') {

                $from = $order->order_status;
                $to = $request->order_status;

                $order->update([
                    'order_status' => $to
                ]);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => "Order status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Order updated to {$to}",
                ]);
            }
            if ($request->mname === 'update_payment_status') {

                $from = $order->payment_status;
                $to = $request->payment_status;

                $order->update([
                    'payment_status' => $to
                ]);
                $this->syncOrderStatus($order);

                broadcast(new OrderUpdated($order));

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'payment_updated',
                    'description' => "Payment status changed to {$to}",
                    'meta' => [
                        'from' => $from,
                        'to' => $to
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Payment updated to {$to}",
                ]);
            }
            if($request->mname === 'mark_as_paid'){
                $from = $order->payment_status;
                $order->update(['payment_status' => 'paid','order_status' => 'processing']);
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'payment_updated',
                    'description' => 'Payment status changed',
                    'meta' => [
                        'from' => $from,
                        'to' => 'paid'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order marked as paid",]);
            }
            if($request->mname === 'mark_as_archived'){
                $order->delete();
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Order status changed to archived',
                    'meta' => [
                        'from' => 'open',
                        'to' => 'archived'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order Archived",]);
            }
            if($request->mname === 'mark_as_restore'){
                $order->restore();
                broadcast(new OrderUpdated($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'status_updated',
                    'description' => 'Order status changed to restored',
                    'meta' => [
                        'from' => 'archived',
                        'to' => 'restored'
                    ],
                    'source' => 'admin',
                ]);
                return response()->json(['success' => true, 'message' => "Order Restored",]);
            }
            if($request->mname === 'send_invoice'){
                $customer = Customer::where('customer_id',$order->customer_id)->first();
                Mail::to($customer->email)
                    ->send(new OrderPlacedMail($order));
                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'invoice_sent',
                    'description' => 'Invoice email sent to customer',
                ]);
                return response()->json(['success' => true, 'message' => "Invoice Sent",]);
            }
            if ($request->mname === 'add_tracking') {

                $order->update([
                    'tracking_number' => $request->tracking_number,
                    'shipment_name' => $request->courier ?? "Royal Mail",
                    'fulfillment_status' => 'fulfilled'
                ]);
                $this->syncOrderStatus($order);

                OrderLog::create([
                    'order_id' => $order->order_id,
                    'event' => 'tracking_added',
                    'description' => 'Tracking added manually',
                    'meta' => [
                        'tracking_number' => $request->tracking_number
                    ],
                    'source' => 'admin',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tracking added'
                ]);
            }
        }
        if(!$order){
            return response()->json(['success'=>false,'message'=>'Order not found']);
        }
        return response()->json(['success'=>true,'order'=>$order]);
    }

    private function syncOrderStatus($order)
    {
        if ($order->order_status === 'archived') {
            return;
        }

        // Completed
        if ($order->fulfillment_status === 'fulfilled') {
            $order->order_status = 'completed';
        }
        // Processing (paid but not fulfilled)
        elseif ($order->payment_status === 'paid') {
            $order->order_status = 'processing';
        }
        // Default
        else {
            $order->order_status = 'pending';
        }

        $order->save();
    }

    private function canTransition($order, $action, $request)
    {
        switch ($action) {

            case 'update_fulfillment_status':
                $to = $request->fulfillment_status;

                if ($to === 'picking') {
                    return $order->payment_status === 'paid'
                        && $order->fulfillment_status === 'unfulfilled';
                }

                if ($to === 'picked') {
                    return in_array($order->fulfillment_status, ['picking']);
                }

                if ($to === 'packed') {
                    return $order->fulfillment_status === 'picked';
                }

                if ($to === 'fulfilled') {
                    return $order->fulfillment_status === 'packed';
                }

                return false;

            case 'update_order_status':
                if ($request->order_status === 'processing') {
                    return $order->payment_status === 'paid';
                }

                if ($request->order_status === 'completed') {
                    return in_array($order->fulfillment_status, ['packed','fulfilled']);
                }
                return false;

            case 'mark_as_paid':
                return in_array($order->payment_status, ['pending','unpaid']);

            default:
                return true;
        }
    }

    public function sendToSendCloudSingle(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id','=',$shopId)->first();
        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }
        $sendcloud = Sendcloud::where('shop_id', $shopId)
            ->where('is_active', true)
            ->first();

        if (!$sendcloud) {
            return response()->json([
                'success' => false,
                'message' => 'No active Sendcloud integration configured for this shop',
            ], 422);
        }

        $payload = $request->input();

        $response = Http::withBasicAuth($sendcloud->public_key, $sendcloud->secret_key)
            ->post('https://panel.sendcloud.sc/api/v2/parcels', $payload);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Sendcloud API error',
                'error' => $response->body(),
            ], $response->status());
        }

        $parcelData = $response->json();
        $parcel = $parcelData['parcel'];
        $order = Order::where('order_number', $parcel['order_number'])
            ->where('shop_id', $shopId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Parcel created in Sendcloud but matching order not found',
            ], 404);
        }

        $order->update([
            'parcel_id' => $parcel['id'],
            'tracking_number' => $parcel['tracking_number'] ?? null,
            'shipment_id' => $parcel['shipping_method'],
            'shipment_name' => $parcel['shipment']['name'] ?? null,
            'label_status' => 'pending',
        ]);

        broadcast(new OrderUpdated($order));

        OrderLog::create([
            'order_id' => $order->order_id,
            'event' => 'status_updated',
            'description' => 'Order label status updated in SendCloud Account',
            'meta' => ['from' => 'no_label', 'to' => 'pending'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created in Sendcloud',
            'parcel' => $parcel,
        ]);

//        $publicKey = $sendcloud->public_key ?? '1636f116-3e02-4da5-a455-492b573b9976';
//        $secretKey = $sendcloud->secret_key ?? '613b5889a7594244a1f4792d7cd62229';

//        $payload = $request->input();
//        $response = Http::withBasicAuth($publicKey, $secretKey)
//            ->post('https://panel.sendcloud.sc/api/v2/parcels', $payload);
//        if($response->successful()){
//            $parcelData = $response->json();
//            $parcel = $parcelData['parcel'];
//            $order = Order::where('order_number','=', $parcel['order_number'])->first();
//            if($order){
//                $order->update([
//                    'parcel_id' => $parcel['id'],
//                    'tracking_number' => $parcel['tracking_number'] ?? null,
//                    'shipment_id' => $parcel['shipping_method'],
//                    'shipment_name' => $parcel['shipment']['name'] ?? null,
//                    'label_status' => 'pending',
//                ]);
//                broadcast(new OrderUpdated($order));
//                OrderLog::create([
//                    'order_id' => $order->order_id,
//                    'event' => 'status_updated',
//                    'description' => 'Order Label Staus updated in SendCloud Account',
//                    'meta' => [
//                        'from'=>'no_label',
//                        'to'=>'pending',
//                    ]
//                ]);
//                return $response->json([
//                    'success' => true,
//                    'message' => 'Order Created in send Cloud'
//                ]);
//            }
//        } else {
//            return response()->json([
//                'success' => false,
//                'message' => 'Sendcloud API error',
//                'error' => $response->body()
//            ], $response->status());
//        }
    }

    public function invoice($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $shop = Shop::find($order->shop_id);
//        $preference = Preference::find($order->shop_id);
        $preference = Preference::firstWhere('shop_id', $order->shop_id);
        $business = BusinessShop::leftjoin('businesses','businesses.business_id','=','business_shops.business_id')
            ->where('business_shops.shop_id','=',$order->shop_id)
            ->first();
        $pdf = Pdf::loadView('pdf.invoice', compact('order','shop','preference','business'));

        return $pdf->download("invoice-{$order->order_id}.pdf");
    }

    public function label($id)
    {
        $order = Order::findOrFail($id);

        $pdf = Pdf::loadView('pdf.label', compact('order'))
            ->setPaper([0, 0, 288, 432]); // 4x6 inch

        return $pdf->download("label-{$order->order_id}.pdf");
    }
}
