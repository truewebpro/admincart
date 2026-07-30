<?php

namespace App\Enums;

// Status of an individual payment record (draft_order_payments row),
// distinct from DraftOrder::payment_status which reflects the aggregate.
enum PaymentRecordStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Refunded => 'Refunded',
        };
    }
}
