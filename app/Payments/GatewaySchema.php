<?php

namespace App\Payments;

/**
 * Single source of truth for the credential fields each provider needs.
 *
 * The backend uses it to build validation rules; the Vue settings form reads
 * the same schema over the API and renders the inputs. Add a provider here and
 * both sides pick it up.
 *
 * Field types: text | password | select | switch
 * `secret: true`  -> never returned to the browser, only ever written.
 * `show_if`       -> render/require only when another field has one of these values.
 */
class GatewaySchema
{
    public const VIVA_WALLET = 'viva_wallet';
    public const WORLDPAY    = 'worldpay';
    public const CYBERSOURCE = 'cybersource';
    public const PAYPAL      = 'paypal';

    public static function providers(): array
    {
        return [self::VIVA_WALLET, self::WORLDPAY, self::CYBERSOURCE, self::PAYPAL];
    }

    public static function all(): array
    {
        return [
            self::VIVA_WALLET => [
                'label'  => 'Viva Wallet',
                'fields' => [
                    [
                        'key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text',
                        'required' => true, 'secret' => false,
                        'hint' => 'Viva banking app → Settings → API access → Access Credentials.',
                    ],
                    [
                        'key' => 'api_key', 'label' => 'API key', 'type' => 'password',
                        'required' => true, 'secret' => true,
                        'hint' => 'Pairs with the merchant ID for basic auth. Not the Smart Checkout secret.',
                    ],
                    [
                        'key' => 'source_code', 'label' => 'Source code', 'type' => 'text',
                        'required' => false, 'secret' => false,
                        'hint' => 'The 4-digit payment source the order was charged through.',
                    ],
                ],
            ],

            self::WORLDPAY => [
                'label'  => 'Worldpay',
                'fields' => [
                    [
                        'key' => 'api_flavour', 'label' => 'Worldpay product', 'type' => 'select',
                        'required' => true, 'secret' => false, 'default' => 'access',
                        'options' => [
                            ['value' => 'access', 'title' => 'Worldpay Access (access.worldpay.com)'],
                            ['value' => 'online', 'title' => 'Online Payments REST (api.worldpay.com)'],
                        ],
                        'hint' => 'The two products authenticate differently — pick the one on your contract.',
                    ],
                    [
                        'key' => 'username', 'label' => 'API username', 'type' => 'text',
                        'required' => true, 'secret' => false, 'show_if' => ['api_flavour' => ['access']],
                    ],
                    [
                        'key' => 'password', 'label' => 'API password', 'type' => 'password',
                        'required' => true, 'secret' => true, 'show_if' => ['api_flavour' => ['access']],
                    ],
                    [
                        'key' => 'entity_reference', 'label' => 'Entity reference', 'type' => 'text',
                        'required' => true, 'secret' => false, 'show_if' => ['api_flavour' => ['access']],
                        'hint' => 'The merchant entity the payment was taken under.',
                    ],
                    [
                        'key' => 'api_version', 'label' => 'Payments API version', 'type' => 'text',
                        'required' => false, 'secret' => false, 'default' => 'v7',
                        'show_if' => ['api_flavour' => ['access']],
                        'hint' => 'Sets the vendor content type, e.g. v6 or v7.',
                    ],
                    [
                        'key' => 'service_key', 'label' => 'Service key', 'type' => 'password',
                        'required' => true, 'secret' => true, 'show_if' => ['api_flavour' => ['online']],
                    ],
                    [
                        'key' => 'client_key', 'label' => 'Client key', 'type' => 'text',
                        'required' => false, 'secret' => false, 'show_if' => ['api_flavour' => ['online']],
                    ],
                ],
            ],

            self::CYBERSOURCE => [
                'label'  => 'Cybersource',
                'fields' => [
                    [
                        'key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text',
                        'required' => true, 'secret' => false,
                    ],
                    [
                        'key' => 'key_id', 'label' => 'Key ID', 'type' => 'text',
                        'required' => true, 'secret' => false,
                        'hint' => 'The serial number from the REST shared-secret key in Business Center.',
                    ],
                    [
                        'key' => 'shared_secret', 'label' => 'Shared secret key', 'type' => 'password',
                        'required' => true, 'secret' => true,
                        'hint' => 'Base64 value generated alongside the key ID.',
                    ],
                    [
                        'key' => 'merchant_descriptor', 'label' => 'Statement descriptor', 'type' => 'text',
                        'required' => false, 'secret' => false,
                        'hint' => 'Shown on the cardholder statement for the credit.',
                    ],
                ],
            ],

            self::PAYPAL => [
                'label'  => 'PayPal',
                'fields' => [
                    [
                        'key' => 'mode', 'label' => 'Mode', 'type' => 'select',
                        'required' => true, 'secret' => false, 'default' => 'live',
                        'options' => [
                            ['value' => 'sandbox', 'title' => 'Sandbox'],
                            ['value' => 'live', 'title' => 'Live'],
                        ],
                        'hint' => 'Picks the API host. Keep this in step with the Environment setting below.',
                    ],
                    [
                        'key' => 'client_id', 'label' => 'Client ID', 'type' => 'text',
                        'required' => true, 'secret' => false,
                        'hint' => 'PayPal Developer Dashboard → Apps & Credentials.',
                    ],
                    [
                        'key' => 'client_secret', 'label' => 'Client secret', 'type' => 'password',
                        'required' => true, 'secret' => true,
                    ],
                    [
                        'key' => 'webhook_id', 'label' => 'Webhook ID', 'type' => 'text',
                        'required' => false, 'secret' => false,
                        'hint' => 'Only needed to verify inbound webhook signatures, not for refunds.',
                    ],
                ],
            ],
        ];
    }

    public static function for(string $provider): array
    {
        $all = self::all();

        if (! isset($all[$provider])) {
            throw new \InvalidArgumentException("Unknown payment provider [{$provider}].");
        }

        return $all[$provider];
    }

    /** Field keys the merchant should never get back once saved. */
    public static function secretKeys(string $provider): array
    {
        return collect(self::for($provider)['fields'])
            ->where('secret', true)
            ->pluck('key')
            ->all();
    }

    /** Build validation rules for a credentials payload, honouring show_if. */
    public static function validationRules(string $provider, array $input): array
    {
        $rules = [];

        foreach (self::for($provider)['fields'] as $field) {
            $applies = true;

            foreach ($field['show_if'] ?? [] as $dependsOn => $values) {
                $applies = $applies && in_array(data_get($input, "credentials.$dependsOn"), $values, true);
            }

            if (! $applies) {
                continue;
            }

            $rules["credentials.{$field['key']}"] = [
                $field['required'] ? 'required' : 'nullable',
                'string',
                'max:512',
            ];
        }

        return $rules;
    }

    /** Strip anything not declared in the schema before persisting. */
    public static function filter(string $provider, array $credentials): array
    {
        $allowed = array_column(self::for($provider)['fields'], 'key');

        return array_intersect_key($credentials, array_flip($allowed));
    }
}
