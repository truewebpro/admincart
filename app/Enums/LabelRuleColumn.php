<?php

namespace App\Enums;

/**
 * Backed by a plain `string` column on product_label_rules — adding a
 * new rule field later (e.g. price, once the variant-price question is
 * resolved) is just adding a case here, no migration needed.
 */
enum LabelRuleColumn: string
{
    case Tag = 'tag';
    case Type = 'type';
    case Brand = 'brand';
    case Title = 'title';

    public function label(): string
    {
        return match ($this) {
            self::Tag => 'Tag',
            self::Type => 'Product Type',
            self::Brand => 'Brand',
            self::Title => 'Title',
        };
    }
}
