<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorldpayWebhookController extends Controller
{
    public function handleWorldPayWebhook(Request $request,$shopname)
    {
        Log::info("Worldpay Webhook",[
            'shopname' => $shopname,
            'shop_id' => $request->shop_id,
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);
        return response()->json([
            'success' => true
        ]);
    }
}
