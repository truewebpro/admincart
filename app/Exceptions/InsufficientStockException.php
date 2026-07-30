<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /**
     * @param array<int, array{variant_id: int, title: string, requested: int, available: int}> $shortages
     */
    public function __construct(private readonly array $shortages)
    {
        $summary = collect($shortages)
            ->map(fn ($s) => "{$s['title']} (requested {$s['requested']}, {$s['available']} available)")
            ->implode(', ');

        parent::__construct("Insufficient stock: {$summary}");
    }

    public function shortages(): array
    {
        return $this->shortages;
    }
}
