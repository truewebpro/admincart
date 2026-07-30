<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Standard VAT rate
    |--------------------------------------------------------------------------
    |
    | There is no backend source of truth for the VAT percentage — variants
    | only store a boolean (istax) indicating whether tax applies at all.
    | The actual rate currently lives as a hardcoded constant in the Next.js
    | storefront's cartContext. This value should be kept in sync with that
    | constant manually until/unless the rate is moved into the `settings`
    | table as a real backend-configurable value.
    |
    | Because istax is boolean rather than a per-product rate, mixed-rate
    | catalogues (0% / 5% / 20% on different products) aren't representable
    | yet — every taxable variant uses this single flat rate.
    |
    */
    'standard_rate' => (float) env('VAT_STANDARD_RATE', 20.00),

];
