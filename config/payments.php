<?php

return [
    // Seconds to wait on any gateway HTTP call.
    'timeout' => env('PAYMENTS_HTTP_TIMEOUT', 30),

    // Used when an order has no currency column of its own.
    'default_currency' => env('PAYMENTS_DEFAULT_CURRENCY', 'GBP'),

    // Log full request/response payloads. Keep off in production: these bodies
    // can contain cardholder data.
    'debug_payloads' => env('PAYMENTS_DEBUG_PAYLOADS', false),
];
