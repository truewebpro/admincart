<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secretKey = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secretKey);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }  catch (\Exception $e){
            return response('Invalid webhook', 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $customerId = $session->customer;
            $priceId = $session->metadata->price_id ?? null;
            $shop = Shop::where('stripe_id', $customerId)->first();
            if($shop && $priceId){
                $plan = Plan::where('stripe_price_id', $priceId)->first();
                if ($plan) {
                    $shop->plan_slug = $plan->slug;
                    $shop->save();
                }
            }
        }

        return response('Webhook Handled.', 200);
    }
}
