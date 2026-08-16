<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Exceptions\SendcloudApiException;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderTrackingEvent;
use App\Models\Sendcloud;
use App\Models\SendcloudShippingOption;
use App\Services\Sendcloud\SendcloudServiceFactory;
use App\Services\Sendcloud\SendcloudStatusMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SendcloudController extends Controller
{
    // GET all options (both active/inactive, enabled/disabled) for the settings screen
    public function getSendcloudOptionSettings(Request $request)
    {
        $shopId = session('shop_id');
        $sendcloud = Sendcloud::where('shop_id', $shopId)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No Sendcloud integration found'], 404);
        }

        $options = SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->orderBy('carrier')
            ->orderBy('name')
            ->get(['id', 'shipping_option_code', 'carrier', 'name', 'is_active', 'is_enabled']);

        return response()->json(['success' => true, 'data' => $options]);
    }

// Bulk save which options are enabled
    public function updateSendcloudOptionSettings(Request $request)
    {
        $shopId = session('shop_id');
        $sendcloud = Sendcloud::where('shop_id', $shopId)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No Sendcloud integration found'], 404);
        }

        $enabledIds = $request->input('enabled_ids', []);

        SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->update(['is_enabled' => false]);

        SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->whereIn('id', $enabledIds)
            ->update(['is_enabled' => true]);

        return response()->json(['success' => true, 'message' => 'Shipping options updated']);
    }

    public function getShippingOptions(Request $request)
    {
        $shopId = session('shop_id');
        $sendcloud = Sendcloud::where('shop_id', $shopId)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No Sendcloud integration found'], 404);
        }

        $options = SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->where('is_active', true)
            ->where('is_enabled', true)
            ->orderBy('carrier')
            ->orderBy('name')
            ->get(['id', 'shipping_option_code', 'carrier', 'name']);

        return response()->json([
            'success' => true,
            'data' => $options,
        ]);
    }

    public function getSendcloudCarriers(Request $request)
    {
        $shopId = session('shop_id');
        $sendcloud = Sendcloud::where('shop_id', $shopId)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No Sendcloud integration found'], 404);
        }

        $carriers = SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->where('is_active', true)
            ->distinct()
            ->orderBy('carrier')
            ->pluck('carrier');

        return response()->json(['success' => true, 'data' => $carriers]);
    }

    public function sendToSendCloudSingle(Request $request)
    {
        $shopId = session('shop_id');
        $sendcloud = Sendcloud::where('shop_id', $shopId)->where('is_active', true)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No active Sendcloud integration'], 422);
        }

        $service = SendcloudServiceFactory::make($sendcloud);

        try {
            $result = $service->createParcel($request->all());
        } catch (SendcloudApiException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sendcloud API error',
                'status' => $e->getStatusCode(),
                'error' => $e->getErrorBody(),
            ], $e->getStatusCode());
        }

        $order = Order::where('order_number', $result['order_number'])
            ->where('shop_id', $shopId)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Look up the friendly service name from our synced table
        $shippingOption = SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->where('shipping_option_code', $result['shipping_option_code'])
            ->first();

        $labelStatus = SendcloudStatusMapper::toLabelStatus($result['status_code'], $result['status_message']);

        $order->update([
            'parcel_id' => $result['id'],
            'tracking_number' => $result['tracking_number'] ?? null,
            'shipment_id' => $result['shipping_option_code'] ?? null,
            'shipment_name' => $shippingOption->name ?? $result['shipping_option_code'] ?? null,
            'label_status' => $labelStatus,
            'fulfillment_status' => $labelStatus === 'created' ? 'fulfilled' : $order->fulfillment_status,
        ]);

        OrderTrackingEvent::create([
            'order_id' => $order->order_id,
            'status_name' => $result['status_message'],
            'status_value' => SendcloudStatusMapper::toTrackingStatus($result['status_message']),
            'status_code' => $result['status_code'],
            'parcel_id' => $result['id'],
            'event_at' => now(),
        ]);

        broadcast(new OrderUpdated($order));
        OrderLog::create([
            'order_id' => $order->order_id,
            'event' => 'status_updated',
            'description' => 'Order label status updated in SendCloud Account',
            'meta' => ['from' => 'no_label', 'to' => $labelStatus],
        ]);

        return response()->json(['success' => true, 'message' => 'Order created in Sendcloud']);
    }

    public function printLabel(Request $request, $orderId)
    {
        $shopId = session('shop_id');
        $order = Order::where('order_id', $orderId)->where('shop_id', $shopId)->first();

        if (!$order || !$order->parcel_id) {
            return response()->json(['success' => false, 'message' => 'No label available for this order'], 404);
        }

        $sendcloud = Sendcloud::where('shop_id', $shopId)->first();

        if (!$sendcloud) {
            return response()->json(['success' => false, 'message' => 'No Sendcloud integration found'], 404);
        }

        $labelUrl = "https://panel.sendcloud.sc/api/v3/parcels/{$order->parcel_id}/documents/label";

        $response = Http::withBasicAuth($sendcloud->public_key, $sendcloud->secret_key)
            ->get($labelUrl);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Could not retrieve label from Sendcloud',
                'error' => $response->json() ?? $response->body(),
            ], $response->status());
        }

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type', 'application/pdf'))
            ->header('Content-Disposition', 'inline; filename="label-' . $order->order_number . '.pdf"');
    }
}
