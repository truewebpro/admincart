<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ShopMailtrapList;
use Illuminate\Support\Facades\Http;

class MailtrapService
{
    protected function headers($apiKey):array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getContactByEmail($mailtrap, $email)
    {
        return Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key))
            ->get('https://mailtrap.io/api/contacts/' . urlencode($email)
        );
    }

    public function updateContact($mailtrap, $customer, $shop)
    {
        return Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->patch('https://mailtrap.io/api/contacts/' . urlencode($customer->email),
            [
                'contact' => [
                    'email' => $customer->email,
                    'fields' => [
                        'first_name' => $customer->fname,
                        'last_name' => $customer->lname,
                        'shopname' => $shop->shop_slug,
                    ],
                    'list_ids' => [
                        (int) $mailtrap->list_id
                    ],
                ]
            ]
        );
    }

    public function getLists($account)
    {
        return Http::withHeaders(
            $this->headers($account->api_key)
        )->get('https://mailtrap.io/api/contacts/lists');
    }

    public function createList($account, $name)
    {
        return Http::withHeaders(
            $this->headers($account->api_key)
        )->post(
            'https://mailtrap.io/api/contacts/lists',
            [
                'name' => $name
            ]
        );
    }

    public function createContact($mailtrap, $customer, $shop)
    {
        return Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->post(
            'https://mailtrap.io/api/contacts',
            [
                'contact' => [
                    'email' => $customer->email,
                    'fields' => [
                        'first_name' => $customer->fname,
                        'last_name' => $customer->lname,
                        'shopname' => $shop->shop_slug,
                    ],
                    'list_ids' => [
                        (int) $mailtrap->list_id
                    ],
                ]
            ]
        );
    }

    public function syncCustomer($customerShop)
    {
        $mailtrap = ShopMailtrapList::with('mailtrapAccount')
            ->where('shop_id', $customerShop->shop_id)
            ->where('enabled', true)
            ->where('sync_customers', true)
            ->first();
        if (!$mailtrap || !$mailtrap->mailtrapAccount) {
            return false;
        }

        $customer = $customerShop->customer;
        $shop = $customerShop->shop;
        if (!$customer || !$customer->email) {
            return false;
        }
        $response = $this->createContact($mailtrap, $customer, $shop);

        if ($response->successful()) {
            $data = $response->json();
            $customerShop->update([
                'mailtrap_contact_id' => $data['data']['id'] ?? null,
                'mailtrap_synced' => true,
                'mailtrap_synced_at' => now(),
                'mailtrap_last_error' => null,
            ]);
            return true;
        }
        $error = $response->json();
        if (isset($error['errors']['email']) &&
            in_array('has already been taken', $error['errors']['email'])) {
            $existingContact = $this->getContactByEmail($mailtrap, $customer->email);
            if ($existingContact->successful()) {
                $contactData = $existingContact->json();
                $customerShop->update([
                    'mailtrap_contact_id' => $contactData['data']['id'] ?? null,
                    'mailtrap_synced' => true,
                    'mailtrap_synced_at' => now(),
                    'mailtrap_last_error' => null,
                ]);
                $this->updateContact($mailtrap, $customer, $shop);
                return true;
            }
        }
        $customerShop->update([
            'mailtrap_synced' => false,
            'mailtrap_last_error' => $response->body(),
        ]);
        return false;
    }

    public function removeCustomer($customerShop)
    {

    }
}
