<?php

namespace App\Enums;

/** Every request the website takes. Each has its own reference prefix, workflow and response target. */
enum LeadType: string
{
    case Order = 'order';
    case Account = 'account';
    case Principal = 'principal';
    case Application = 'application';
    case Enquiry = 'enquiry';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Order request',
            self::Account => 'Trade account application',
            self::Principal => 'Principal enquiry',
            self::Application => 'Job application',
            self::Enquiry => 'Enquiry',
        };
    }

    /** Two-letter prefix of the customer reference, e.g. OR-26-00042. */
    public function prefix(): string
    {
        return match ($this) {
            self::Order => 'OR',
            self::Account => 'TA',
            self::Principal => 'PR',
            self::Application => 'CV',
            self::Enquiry => 'EN',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Order => 'crate',
            self::Account => 'shield',
            self::Principal => 'globe',
            self::Application => 'users',
            self::Enquiry => 'message',
        };
    }

    /**
     * How long a new request may wait before it counts as overdue. Order requests are
     * the trade desk's daily work, so their target is the tightest.
     */
    public function responseHours(): int
    {
        return match ($this) {
            self::Order => 2,
            self::Enquiry => 24,
            self::Account, self::Principal => 48,
            self::Application => 120,
        };
    }

    public function targetLabel(): string
    {
        $h = $this->responseHours();

        return $h < 24 ? $h.' hours' : ($h / 24).' days';
    }

    /**
     * The workflow for this type, in order. The first is always New.
     *
     * @return list<LeadStatus>
     */
    public function statuses(): array
    {
        return match ($this) {
            self::Order => [LeadStatus::New, LeadStatus::Confirmed, LeadStatus::Dispatched, LeadStatus::Delivered, LeadStatus::Cancelled],
            self::Account => [LeadStatus::New, LeadStatus::Reviewing, LeadStatus::NeedsDocuments, LeadStatus::Approved, LeadStatus::Declined],
            self::Principal => [LeadStatus::New, LeadStatus::Contacted, LeadStatus::InDiscussion, LeadStatus::Closed, LeadStatus::Declined],
            self::Application => [LeadStatus::New, LeadStatus::Shortlisted, LeadStatus::Interview, LeadStatus::Hired, LeadStatus::Declined],
            self::Enquiry => [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Closed],
        };
    }

    public function allows(LeadStatus $status): bool
    {
        return in_array($status, $this->statuses(), true);
    }
}
