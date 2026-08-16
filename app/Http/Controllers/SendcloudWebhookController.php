<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderTrackingEvent;
use App\Models\Shop;
use App\Services\Sendcloud\SendcloudStatusMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendcloudWebhookController extends Controller
{
    public function handleSendcloudWebhook(Request $request)
    {
        Log::info('✅ Sendcloud Webhook Received:', $request->all());
        $payload = $request->all();
        if (!isset($payload['parcel']['id'])) {
            return response()->json(['message' => 'No parcel data'], 200);
        }

        $parcel = $payload['parcel'];
        $tracking = $parcel['tracking_number'] ?? null;
        $labelUrl = $parcel['label']['normal_printer'][0] ?? null;
        $shipmentStatus = $parcel['status']['message'] ?? 'updated';
        $sendcloudParcelId = $parcel['id'];
        $courierName = $parcel['carrier']['name'] ?? null;

        $order = Order::where('parcel_id','=',$sendcloudParcelId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 200);
        }
        if($shipmentStatus == 'Being announced'){
            $order->update(['label_status' => 'pending']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'no_label',
                    'to'=>'pending',
                ]
            ]);
        } elseif ($shipmentStatus == 'Ready to send'){
            $order->update(['label_status' => 'created','fulfillment_status' => 'fulfilled']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'pending',
                    'to'=>'created',
                ]
            ]);
        } elseif ($shipmentStatus == 'Parcel en route'){
            $order->update(['label_status' => 'printed','order_status' => 'completed','fulfillment_status' => 'fulfilled']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'created',
                    'to'=>'printed',
                ]
            ]);
        }
        $order->update([
            'tracking_number' => $tracking,
        ]);
        broadcast(new OrderUpdated($order));
        if($tracking) {
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Tracking Staus updated ' . $tracking,
                'meta' => [
                    'from' => 'no_label',
                    'to' => 'pending',
                ]
            ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    public function handleVapeportSendcloudWebhook(Request $request)
    {
        Log::info('✅ Sendcloud Vapeport Webhook Received:', $request->all());
        $payload = $request->all();
        if (!isset($payload['parcel']['id'])) {
            return response()->json(['message' => 'No parcel data'], 200);
        }

        $parcel = $payload['parcel'];
        $tracking = $parcel['tracking_number'] ?? null;
        $labelUrl = $parcel['label']['normal_printer'][0] ?? null;
        $shipmentStatus = $parcel['status']['message'] ?? 'updated';
        $sendcloudParcelId = $parcel['id'];
        $courierName = $parcel['carrier']['name'] ?? null;

        $order = Order::where('parcel_id','=',$sendcloudParcelId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 200);
        }
        if($shipmentStatus == 'Being announced'){
            $order->update(['label_status' => 'pending']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'no_label',
                    'to'=>'pending',
                ]
            ]);
        } elseif ($shipmentStatus == 'Ready to send'){
            $order->update(['label_status' => 'created','fulfillment_status' => 'fulfilled']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'pending',
                    'to'=>'created',
                ]
            ]);
        } elseif ($shipmentStatus == 'Parcel en route'){
            $order->update(['label_status' => 'printed','order_status' => 'completed','fulfillment_status' => 'fulfilled']);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Label Staus updated in SendCloud Account',
                'meta' => [
                    'from'=>'created',
                    'to'=>'printed',
                ]
            ]);
        }
        $order->update([
            'tracking_number' => $tracking,
        ]);
        broadcast(new OrderUpdated($order));
        if($tracking) {
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order Tracking Staus updated ' . $tracking,
                'meta' => [
                    'from' => 'no_label',
                    'to' => 'pending',
                ]
            ]);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Sendcloud webhook — receives status updates for a specific shop.
     * Route example: POST /webhooks/sendcloud/{shopname}
     */
    public function handleSendCloudWebHookEvents(Request $request, $shopname)
    {
        Log::info('✅ Sendcloud Webhook Received:', ['shop' => $shopname, 'payload' => $request->all()]);

        $shop = Shop::where('shop_name', $shopname)->first();
        if (!$shop) {
            return response()->json(['message' => 'Unknown shop'], 200);
        }

        $payload = $request->all();
        if (!isset($payload['parcel']['id'])) {
            return response()->json(['message' => 'No parcel data'], 200);
        }

        $parcel = $payload['parcel'];
        $tracking = $parcel['tracking_number'] ?? null;
        $shipmentStatus = $parcel['status']['message'] ?? 'updated';
        $sendcloudParcelId = $parcel['id'];
        $courierName = $parcel['carrier']['name'] ?? null;

        // Scope to the shop so parcel IDs never cross tenants
        $order = Order::where('parcel_id', $sendcloudParcelId)
            ->where('shop_id', $shop->shop_id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 200);
        }

        // 1. Always log the raw event for history/audit — every status Sendcloud sends
        OrderTrackingEvent::create([
            'order_id' => $order->order_id,
            'status_name' => $shipmentStatus,
            'status_value' => SendcloudStatusMapper::toTrackingStatus($shipmentStatus),
            'status_code' => $parcel['status']['code'] ?? null,
            'parcel_id' => $sendcloudParcelId,
            'carrier' => $courierName,
            'event_at' => now(),
        ]);

        // 2. Only update the workflow enum when this event maps to a label-lifecycle stage
        $newLabelStatus = SendcloudStatusMapper::toLabelStatus($parcel['status']['code'] ?? null, $shipmentStatus);

        if ($newLabelStatus !== $order->label_status) {
            $previousLabelStatus = $order->label_status;
            $updates = ['label_status' => $newLabelStatus];

            if ($newLabelStatus === 'created') {
                $updates['fulfillment_status'] = 'fulfilled';
            }
            if ($newLabelStatus === 'printed') {
                $updates['order_status'] = 'completed';
            }

            $order->update($updates);

            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order label status updated in SendCloud Account',
                'meta' => ['from' => $previousLabelStatus, 'to' => $newLabelStatus],
            ]);
        }

        // 3. Tracking number — guarded, fires only on genuine change
        if ($tracking && $tracking !== $order->tracking_number) {
            $order->update(['tracking_number' => $tracking]);
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order tracking number updated: ' . $tracking,
                'meta' => ['tracking_number' => $tracking],
            ]);
        }

        broadcast(new OrderUpdated($order));

        return response()->json(['message' => 'Webhook processed'], 200);
    }
}
