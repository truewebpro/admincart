<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderLog;
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
}
