<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Subscription as StripeSubscription;
use Laravel\Cashier\Subscription;

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
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $customerId = $session->customer;
            $subscriptionId = $session->subscription;
            $priceId = $session->metadata->price_id ?? null;
            $shop = Shop::where('stripe_id', $customerId)->first();
            if($shop && $subscriptionId){
                $stripeSub = StripeSubscription::retrieve($subscriptionId);
                $exists = Subscription::where('stripe_id', $stripeSub->id)->exists();
                if(!$exists){
                    $priceIdFromStripe = $stripeSub->items->data[0]->price->id ?? null;
                    Subscription::create([
                        'shop_id' => $shop->shop_id,
                        'type' => 'default',
                        'stripe_id' => $stripeSub->id,
                        'stripe_status' => $stripeSub->status,
                        'stripe_price' => $priceIdFromStripe,
                        'quantity' => 1,
                        'trial_ends_at' => null,
                        'ends_at' => $stripeSub->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSub->cancel_at) : null,
                    ]);
                }
                // update plan
                if ($priceId) {
                    $plan = Plan::where('stripe_price_id', $priceId)->first();
                    if ($plan) {
                        $shop->plan_slug = $plan->slug;
                        $shop->save();
                    }
                }
            }
        }

        if ($event->type === 'customer.subscription.updated') {
            $sub = $event->data->object;

            Subscription::where('stripe_id', $sub->id)->update([
                'stripe_status' => $sub->status,
                'stripe_price' => $sub->items->data[0]->price->id ?? null,
            ]);
        }

        if ($event->type === 'customer.subscription.deleted') {
            $sub = $event->data->object;

            Subscription::where('stripe_id', $sub->id)->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);
        }

        return response('Webhook Handled.', 200);
    }

    public function syncExisting()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $shops = Shop::whereNotNull('stripe_id')->get();
        foreach ($shops as $shop) {
            $stripeSubs = StripeSubscription::all([
                'customer' => $shop->stripe_id,
            ]);

            foreach ($stripeSubs->data as $sub) {

                $exists = Subscription::where('stripe_id', $sub->id)->exists();
                if (!$exists) {
                    Subscription::create([
                        'shop_id' => $shop->shop_id,
                        'type' => 'default',
                        'stripe_id' => $sub->id,
                        'stripe_status' => $sub->status,
                        'stripe_price' => $sub->items->data[0]->price->id ?? null,
                        'quantity' => 1,
                        'trial_ends_at' => null,
                        'ends_at' => null,
                    ]);
                }
            }
        }

        return "Synced";
    }
}
