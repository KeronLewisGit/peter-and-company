<?php

namespace App\Enums;

/** The kind of business a trade customer runs. Pharmacies and clinics are the only buyers of the pharmaceutical range. */
enum TradeType: string
{
    case Supermarket = 'supermarket';
    case Minimart = 'minimart';
    case Bar = 'bar';
    case Hotel = 'hotel';
    case Pharmacy = 'pharmacy';
    case Clinic = 'clinic';
    case Wholesale = 'wholesale';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Supermarket => 'Supermarket',
            self::Minimart => 'Minimart, shop or gas station',
            self::Bar => 'Bar, restaurant or rum shop',
            self::Hotel => 'Hotel, resort or caterer',
            self::Pharmacy => 'Licensed pharmacy',
            self::Clinic => 'Hospital, clinic or medical practice',
            self::Wholesale => 'Wholesaler or reseller',
            self::Other => 'Other business',
        };
    }

    /** May buy the pharmaceutical range (with a licence on file). */
    public function isMedical(): bool
    {
        return in_array($this, [self::Pharmacy, self::Clinic], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
