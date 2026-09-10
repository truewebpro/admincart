<?php

namespace App\Support;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The admin panel keeps the active shop in the session, so nothing shop-related
 * appears in the URL. Every controller action goes through here.
 */
trait ResolvesCurrentShop
{
    protected function currentShopId(): int
    {
        $shopId = session('shop_id');

        if (! $shopId) {
            throw new HttpException(409, 'No shop is selected for this session.');
        }

        return (int) $shopId;
    }

    /**
     * Guard against a record from another tenant leaking through route binding.
     * Anything that does not belong to the session shop is a 404, not a 403 —
     * the admin should not learn that the record exists.
     */
    protected function assertBelongsToCurrentShop(?object $model): void
    {
        abort_if($model === null, 404);
        abort_unless((int) $model->shop_id === $this->currentShopId(), 404);
    }
}
