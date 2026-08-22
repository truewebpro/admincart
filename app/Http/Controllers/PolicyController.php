<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PolicyController extends Controller
{
    public function getPolicyBySlug(Request $request,$shopname,$policy_slug)
    {
        $shopId = $request->shop_id;
        $policy = Cache::remember(
            CacheKeys::policy($shopId,$policy_slug),
            now()->addDays(7),
            function () use ($shopId,$policy_slug){
                return Policy::where('shop_id','=',$shopId)
                    ->where('policy_slug','=',$policy_slug)
                    ->first();
            }
        );
        return response()->json([
            'status' => !is_null($policy),
            'policy' => $policy,
        ]);
    }

    //Admin Routes
    public function getPolicyList()
    {
        $shopId = session('shop_id');
        $policies = Policy::where('shop_id', $shopId)->get();
        if($policies->isEmpty()){
            $defaultPolicies = [
                [
                    'policy_name' => 'Terms and Conditions',
                    'policy_slug'  => 'terms',
                    'policy_description' => 'By using this site, you agree to the terms...',
                ],
                [
                    'policy_name' => 'Privacy Policy',
                    'policy_slug'  => 'privacy',
                    'policy_description' => 'Your privacy is important to us...',
                ],
                [
                    'policy_name' => 'Refund Policy',
                    'policy_slug'  => 'refund-policy',
                    'policy_description' => 'We offer refunds within 30 days...',
                ],
                [
                    'policy_name' => 'Shipping Policy',
                    'policy_slug'  => 'shipping-policy',
                    'policy_description' => 'We ship products within 3-5 business days...',
                ],
            ];
            foreach ($defaultPolicies as &$policy) {
                $policy['policy_status'] = 'active';
                $policy['shop_id'] = $shopId;
            }

            Policy::insert($defaultPolicies);
            $policies = Policy::where('shop_id', $shopId)->get();
            return response()->json([
                'success' => false,
                'policies' => $policies,
            ]);
        }
        return response()->json([
            'success' => true,
            'policies' => $policies,
        ]);

    }

    public function getPolicyById($policy_id)
    {
        $shopId = session('shop_id');
        $policy = Policy::where('shop_id', $shopId)
            ->where('policy_id', $policy_id)
            ->first();
        if($policy){
            return response()->json([
                'success' => true,
                'policy' => $policy,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'policy' => 'null',
            ]);
        }
    }

    public function updatePolicy(Request $request)
    {
        $shopId = session('shop_id');
        DB::beginTransaction();
        try {
            $policy = Policy::where('shop_id', $shopId)->where('policy_id', $request->policy_id)->firstOrFail();

            $policy->policy_name = $request->policy_name;
            $policy->policy_description = $request->policy_description;
            $policy->update();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Policy has been updated.',
                'policy' => $policy,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
