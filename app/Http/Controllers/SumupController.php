<?php

namespace App\Http\Controllers;

use App\Models\Acart;
use App\Models\AcartEvent;
use App\Models\Sumup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SumupController extends Controller
{
    public function getAccessToken($sumup)
    {
        $baseUrl = 'https://api.sumup.com';
        $response = Http::asForm()->post($baseUrl.'/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $sumup->client_id,
            'client_secret' => $sumup->client_secret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('SumUp token failed: '.$response->body());
        }

        return $response->json()['access_token'];
    }

    public function createCheckout(Request $request)
    {
        $shop = $request->attributes->get('shop');
        $shopId = $shop->shop_id;
        $sumup = Sumup::where('shop_id',$shopId)->first();
        $token = $this->getAccessToken($sumup);
        $acart = Acart::where('shop_id', $shopId)
            ->where('checkout_id', $request->checkout_id)
            ->firstOrFail();
        $reference = $acart->checkout_id;

        $response = Http::withToken($token)->post('https://api.sumup.com/v0.1/checkouts', [
            'checkout_reference' => $reference,
            'amount' => $request->amount,
            'currency' => 'GBP',
            'pay_to_email' => $sumup->merchant_email,
            'description' => 'Order Payment',
            'return_url' => "https://{$shop->maindomain}/payment/success?reference={$reference}",
        ]);
        if (!$response->successful()) {
            return response()->json([
                'error' => 'SumUp checkout failed',
                'details' => $response->json()
            ], 500);
        }
        $acart->update([
            'cart_status' => 'sumup_payment_start',
        ]);
        AcartEvent::create([
            'acart_id' => $acart->acart_id,
            'shop_id' => $shopId,
            'event_type' => 'sumup_payment_start',
            'event_data'=> $response->json(),
        ]);

        return response()->json($response->json());
    }

    public function verify(Request $request)
    {
        $checkoutId = $request->checkout_id;

        $shop = $request->attributes->get('shop');
        $shopId = $shop->shop_id;
        $sumup = Sumup::where('shop_id',$shopId)->first();
        $token = $this->getAccessToken($sumup);
        $response = Http::withToken($token)
            ->get("https://api.sumup.com/v0.1/checkouts/{$checkoutId}");

        $status = $response->json()['status'];

        if ($status === 'PAID') {
            $sumup = Sumup::where('shop_id', $shopId)->firstOrFail();
            // mark order completed
        }
    }
}
