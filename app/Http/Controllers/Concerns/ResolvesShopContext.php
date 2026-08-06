<?php

namespace App\Http\Controllers\Concerns;

/**
 * Shared by every Admin controller that needs to scope a query/write to the
 * currently active shop. Centralizes the session read + missing-context guard
 * in one place instead of repeating `(int) session('shop_id')` inline
 * everywhere with inconsistent null-handling.
 */
trait ResolvesShopContext
{
    private function currentShopId(): int
    {
        $shopId = session('shop_id');

        abort_unless($shopId, 403, 'No shop context.');

        return (int) $shopId;
    }
}
