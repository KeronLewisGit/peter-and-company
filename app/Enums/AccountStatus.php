<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'good',
            self::OnHold => 'attention',
            self::Closed => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'check-circle',
            self::OnHold => 'pause',
            self::Closed => 'x',
        };
    }
}
