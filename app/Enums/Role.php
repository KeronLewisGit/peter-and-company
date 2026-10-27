<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Credit = 'credit';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Sales => 'Sales & trade desk',
            self::Credit => 'Credit control',
            self::Hr => 'Human resources',
        };
    }

    /**
     * Request types this role works in the inbox.
     *
     * @return list<LeadType>
     */
    public function leadTypes(): array
    {
        return match ($this) {
            self::Admin => LeadType::cases(),
            self::Sales => [LeadType::Order, LeadType::Account, LeadType::Principal, LeadType::Enquiry],
            self::Credit => [LeadType::Account],
            self::Hr => [LeadType::Application],
        };
    }
}
