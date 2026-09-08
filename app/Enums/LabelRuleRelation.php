<?php

namespace App\Enums;

enum LabelRuleRelation: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case NotContains = 'not_contains';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Is',
            self::NotEquals => 'Is not',
            self::Contains => 'Contains',
            self::NotContains => "Doesn't contain",
        };
    }
}
