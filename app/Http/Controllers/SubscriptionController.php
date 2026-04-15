<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Shop;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    public function subscribe(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }
        $plan = Plan::where('slug', $request->plan)->firstOrFail();
        $shop->createOrGetStripeCustomer();
        if ($shop->subscribed('default')) {
            $shop->subscription('default')
                ->swap($plan->stripe_price_id);
            $shop->plan_slug = $plan->slug;
            $shop->save();

            return response()->json([
                'message' => 'Plan updated'
            ]);
        }
        $checkout = $shop->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => url('/settings/account'),
                'cancel_url' => url('/settings/account'),
            ],[
                'metadata' => [
                    'price_id' => $plan->stripe_price_id,
                ]
            ]);
        return response()->json([
            'url' => $checkout->url
        ]);
    }

    public function current(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            return response()->json([
                'plan' => null,
                'subscribed' => false
            ]);
        }
        return response()->json([
            'plan' => $shop->plan,
            'subscribed' => $shop->subscribed('default'),
        ]);
    }

    public function billing(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }
        return response()->json([
            'plan' => $shop->plan,
            'subscribed' => $shop->subscribed('default'),
            'card' => [
                'brand' => $shop->pm_type,
                'last4' => $shop->pm_last_four,
            ],
            'grace_period' => optional($shop->subscription('default'))->onGracePeriod(),
            'ends_at' => optional($shop->subscription('default'))->ends_at,
            'invoices' => $shop->invoices()->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'url' => $invoice->hosted_invoice_url,
                ];
            }),
        ]);
    }

    public function cancel()
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->first();

        if ($shop->subscribed('default')) {
            $shop->subscription('default')->cancel();
        }

        return response()->json(['message' => 'Subscription cancelled']);
    }
}
