<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerShop;
use App\Models\ShopMailtrapList;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailtrapService
{
    protected string $baseUrl = 'https://mailtrap.io/api';

    protected function headers($apiKey): array
    {
        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get a Mailtrap contact by email.
     */
    public function getContactByEmail($mailtrap, $email)
    {
        return Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->get(
            $this->baseUrl . '/contacts/' . urlencode($email)
        );
    }

    /**
     * Update an existing Mailtrap contact.
     *
     * list_ids_included adds the current shop's list
     * without removing the customer's existing lists.
     */
    public function updateContact($mailtrap, $customer)
    {
        $listId = (int) $mailtrap->list_id;

        $payload = [
            'contact' => [
                'email' => $customer->email,

                'fields' => [
                    'first_name' => $customer->fname,
                    'last_name' => $customer->lname,
                ],

                'list_ids_included' => [
                    $listId
                ],
            ],
        ];

        $response = Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->patch(
            $this->baseUrl . '/contacts/' . urlencode($customer->email),
            $payload
        );

        return $response;
    }

    /**
     * Get all Mailtrap contact lists.
     */
    public function getLists($account)
    {
        return Http::withHeaders(
            $this->headers($account->api_key)
        )->get(
            $this->baseUrl . '/contacts/lists'
        );
    }

    /**
     * Create a Mailtrap contact list.
     */
    public function createList($account, $name)
    {
        return Http::withHeaders(
            $this->headers($account->api_key)
        )->post(
            $this->baseUrl . '/contacts/lists',
            [
                'name' => $name,
            ]
        );
    }

    /**
     * Create a new Mailtrap contact.
     */
    public function createContact($mailtrap, $customer)
    {
        return Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->post(
            $this->baseUrl . '/contacts',
            [
                'contact' => [
                    'email' => $customer->email,

                    'fields' => [
                        'first_name' => $customer->fname,
                        'last_name' => $customer->lname,
                    ],

                    'list_ids' => [
                        (int) $mailtrap->list_id
                    ],
                ],
            ]
        );
    }

    /**
     * Sync a customer/shop relationship to Mailtrap.
     *
     * One Mailtrap contact can belong to multiple shop lists.
     */
    public function syncCustomer(CustomerShop $customerShop): bool
    {
        $mailtrap = ShopMailtrapList::with('mailtrapAccount')
            ->where('shop_id', $customerShop->shop_id)
            ->where('enabled', true)
            ->where('sync_customers', true)
            ->first();

        if (!$mailtrap || !$mailtrap->mailtrapAccount) {

            $customerShop->update([
                'mailtrap_synced' => false,
                'mailtrap_last_error' => 'Mailtrap configuration not found or disabled.',
            ]);

            return false;
        }

        $customer = $customerShop->customer;
        $shop = $customerShop->shop;

        if (!$customer || !$customer->email) {

            $customerShop->update([
                'mailtrap_synced' => false,
                'mailtrap_last_error' => 'Customer email is missing.',
            ]);

            return false;
        }

        /*
         * Check if the contact already exists.
         */
        $existingContactResponse = $this->getContactByEmail(
            $mailtrap,
            $customer->email
        );

        /*
         * Existing contact
         */
        if ($existingContactResponse->successful()) {

            $contactData = $existingContactResponse->json()['data'] ?? [];

            $contactId = $contactData['id'] ?? null;

            /*
             * Add this shop's Mailtrap list.
             *
             * IMPORTANT:
             * We use list_ids_included here.
             * This preserves existing list memberships.
             */
            $response = $this->updateContact(
                $mailtrap,
                $customer
            );

            if ($response->successful()) {

                $responseData = $response->json()['data'] ?? [];

                $customerShop->update([
                    'mailtrap_contact_id' => $responseData['id']
                        ?? $contactId,
                    'mailtrap_synced' => true,
                    'mailtrap_synced_at' => now(),
                    'mailtrap_last_error' => null,
                ]);

                return true;
            }

            $customerShop->update([
                'mailtrap_synced' => false,
                'mailtrap_last_error' => $response->body(),
            ]);

            return false;
        }

        /*
         * Contact does not exist.
         * Create it with this shop's list.
         */
        $response = $this->createContact(
            $mailtrap,
            $customer
        );

        if ($response->successful()) {

            $data = $response->json()['data'] ?? [];

            $customerShop->update([
                'mailtrap_contact_id' => $data['id'] ?? null,
                'mailtrap_synced' => true,
                'mailtrap_synced_at' => now(),
                'mailtrap_last_error' => null,
            ]);

            return true;
        }

        /*
         * In case the contact was created by another request
         * between our GET and POST, try fetching it again.
         */
        $error = $response->json();

        if (
            isset($error['errors']['email']) &&
            in_array(
                'has already been taken',
                $error['errors']['email'],
                true
            )
        ) {

            $existingContact = $this->getContactByEmail(
                $mailtrap,
                $customer->email
            );

            if ($existingContact->successful()) {

                $contactData = $existingContact->json()['data'] ?? [];

                $updateResponse = $this->updateContact(
                    $mailtrap,
                    $customer
                );

                if ($updateResponse->successful()) {

                    $updateData = $updateResponse->json()['data'] ?? [];

                    $customerShop->update([
                        'mailtrap_contact_id' =>
                            $updateData['id']
                            ?? $contactData['id']
                                ?? null,

                        'mailtrap_synced' => true,
                        'mailtrap_synced_at' => now(),
                        'mailtrap_last_error' => null,
                    ]);

                    return true;
                }
            }
        }

        /*
         * Sync failed.
         */
        $customerShop->update([
            'mailtrap_synced' => false,
            'mailtrap_last_error' => $response->body(),
        ]);

        return false;
    }

    /**
     * Remove customer from this shop's Mailtrap list.
     *
     * This does NOT delete the Mailtrap contact.
     * It only removes the customer from this shop's list.
     */
    public function removeCustomer(CustomerShop $customerShop): bool
    {
        $mailtrap = ShopMailtrapList::with('mailtrapAccount')
            ->where('shop_id', $customerShop->shop_id)
            ->where('enabled', true)
            ->first();

        if (!$mailtrap || !$mailtrap->mailtrapAccount) {
            return false;
        }

        $customer = $customerShop->customer;

        if (!$customer || !$customer->email) {
            return false;
        }

        $listId = (int) $mailtrap->list_id;

        $response = Http::withHeaders(
            $this->headers($mailtrap->mailtrapAccount->api_key)
        )->patch(
            $this->baseUrl . '/contacts/' . urlencode($customer->email),
            [
                'contact' => [
                    'email' => $customer->email,

                    'list_ids_excluded' => [
                        $listId
                    ],
                ],
            ]
        );

        if ($response->successful()) {

            $customerShop->update([
                'mailtrap_synced' => false,
                'mailtrap_synced_at' => null,
            ]);

            return true;
        }

        $customerShop->update([
            'mailtrap_last_error' => $response->body(),
        ]);

        return false;
    }
}
