<?php

namespace App\Http\Controllers;

use App\Models\MailtrapAccount;
use App\Models\ShopMailtrapList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MailtrapController extends Controller
{
    public function getMailtrapLists()
    {
//        $account = MailtrapAccount::find($accountId);
        $account = MailtrapAccount::first();
        $response = Http::withToken($account->api_key)
            ->acceptJson()
            ->get('https://mailtrap.io/api/contacts/lists');

        return response()->json([
            'status' => $response->status(),
            'success' => $response->successful(),
            'lists' => $response->json(),
        ]);
    }

    public function createMailtrapList(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shop_id' => 'required|exists:shops,shop_id',
        ]);
        $account = MailtrapAccount::first();
        $response = Http::withHeaders([
            'Api-Token' => $account->api_key,
            'Accept' => 'application/json',
        ])->post(
            'https://mailtrap.io/api/contacts/lists',
            [
                'name' => $request->name,
            ]
        );
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
