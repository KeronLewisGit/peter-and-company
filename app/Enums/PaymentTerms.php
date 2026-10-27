<?php

namespace App\Enums;

/** What a trade customer asks for on the application, and what credit control grants. */
enum PaymentTerms: string
{
    case Cod = 'cod';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash or cheque on delivery',
            self::Credit => 'Credit account (terms set by credit control)',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Cod => 'COD',
            self::Credit => 'Credit',
        };
    }
}
