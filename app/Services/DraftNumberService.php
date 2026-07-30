<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopNumberSequence;
use Illuminate\Support\Facades\DB;

class DraftNumberService
{
    private const PREFIX = 'DF-';
    private const PAD_LENGTH = 4;

    /**
     * Generate the next draft order number for the given shop.
     *
     * Concurrency-safe: locks the shop's sequence row for the duration of
     * the transaction (SELECT ... FOR UPDATE via lockForUpdate), so two
     * simultaneous "Save Draft" requests for the same shop cannot receive
     * the same number. Must be called from within a DB transaction that
     * also persists the DraftOrder — see DraftOrderService::create().
     *
     * Reusable for other sequence types (e.g. 'order', 'invoice') by
     * passing a different $sequenceType.
     */
    public function next(Shop $shop, string $sequenceType = 'draft_order'): string
    {
        $sequence = ShopNumberSequence::query()
            ->where('shop_id', $shop->getKey())
            ->where('sequence_type', $sequenceType)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            // firstOrCreate is not safe here under race conditions on the
            // very first number; rely on the unique(shop_id, sequence_type)
            // constraint and retry on collision instead.
            try {
                $sequence = ShopNumberSequence::query()->create([
                    'shop_id' => $shop->getKey(),
                    'sequence_type' => $sequenceType,
                    'last_number' => 0,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Another concurrent request created it first — re-fetch with lock.
                $sequence = ShopNumberSequence::query()
                    ->where('shop_id', $shop->getKey())
                    ->where('sequence_type', $sequenceType)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        }

        $sequence->increment('last_number');

        return self::PREFIX . str_pad(
            (string) $sequence->last_number,
            self::PAD_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }
}
