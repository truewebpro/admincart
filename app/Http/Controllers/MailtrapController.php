<?php

namespace App\Http\Controllers;

use App\Models\CustomerShop;
use App\Models\MailtrapAccount;
use App\Models\ShopMailtrapList;
use App\Services\MailtrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MailtrapController extends Controller
{
    public function getMailtrapLists(MailtrapService $mailtrapService)
    {
        $account = MailtrapAccount::first();
        $response = $mailtrapService->getLists($account);
        return response()->json([
            'status' => $response->status(),
            'success' => $response->successful(),
            'lists' => $response->json(),
        ]);
    }

    public function createMailtrapList(Request $request,MailtrapService $mailtrapService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shop_id' => 'required|exists:shops,shop_id',
        ]);
        $account = MailtrapAccount::first();
        $response = $mailtrapService->createList($account, $request->name);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'error' => $response->json(),
            ], 500);
        }
        $list = $response->json();
        $mapping = ShopMailtrapList::updateOrCreate(
            [
                'shop_id' => $request->shop_id,
            ],
            [
                'mailtrap_account_id' => $account->id,
                'list_id' => $list['id'],
                'list_name' => $list['name'],
                'enabled' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'list' => $list,
            'mapping' => $mapping,
        ]);
    }

    public function syncAllCustomers(MailtrapService $mailtrapService)
    {
        $customerShops = CustomerShop::with(['customer','shop'])
            ->where('mailtrap_synced', false)
            ->whereNull('mailtrap_contact_id')
            ->whereHas('customer', function ($q) {
                $q->whereNotNull('email');
            })
            ->get();
        $success = 0;
        $failed = 0;
        foreach ($customerShops as $customerShop) {
            $synced = $mailtrapService
                ->syncCustomer($customerShop);
            if ($synced) {
                $success++;
            } else {
                $failed++;
            }
        }
        return response()->json([
            'success' => true,
            'synced' => $success,
            'failed' => $failed,
        ]);
    }

    public function saveShopMailtrapList(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,shop_id',
            'mailtrap_account_id' => 'required|exists:mailtrap_accounts,id',
            'list_id' => 'required',
            'list_name' => 'required',
        ]);

        $mapping = ShopMailtrapList::updateOrCreate(
            [
                'shop_id' => $validated['shop_id']
            ],
            [
                'mailtrap_account_id' => $validated['mailtrap_account_id'],
                'list_id' => $validated['list_id'],
                'list_name' => $validated['list_name'],
                'enabled' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'mapping' => $mapping
        ]);
    }
}
