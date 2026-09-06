<?php

namespace App\Enums;

/**
 * Backed by a plain `string` column (not a DB-native enum), so adding a
 * new position later — e.g. top-center, bottom-center — is just adding a
 * case here, no migration needed. This enum is what gives type safety
 * and validation in application code instead.
 */
enum BadgePosition: string
{
    case TopCenter = 'top-center';
    case TopLeft = 'top-left';
    case TopRight = 'top-right';
    case BottomCenter = 'bottom-center';
    case BottomLeft = 'bottom-left';
    case BottomRight = 'bottom-right';
    case Inline = 'inline';

    public function label(): string
    {
        return match ($this) {
            self::TopCenter => 'Top Center',
            self::TopLeft => 'Top Left',
            self::TopRight => 'Top Right',
            self::BottomCenter => 'Bottom Center',
            self::BottomLeft => 'Bottom Left',
            self::BottomRight => 'Bottom Right',
            self::Inline => 'Inline',
        };
    }
}
