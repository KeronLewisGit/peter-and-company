<?php

namespace App\Enums;

/**
 * Every stage any request can be in; LeadType::statuses() gives the stages one type uses.
 * Colour is never the only signal: each has an icon and text.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Confirmed = 'confirmed';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Reviewing = 'reviewing';
    case NeedsDocuments = 'needs_documents';
    case Approved = 'approved';
    case InDiscussion = 'in_discussion';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Hired = 'hired';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Confirmed => 'Confirmed',
            self::Dispatched => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Reviewing => 'Reviewing',
            self::NeedsDocuments => 'Needs documents',
            self::Approved => 'Approved',
            self::InDiscussion => 'In discussion',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Hired => 'Hired',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
            self::Declined => 'Declined',
        };
    }

    /** Finished: nothing more to do. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Approved, self::Hired, self::Closed, self::Cancelled, self::Declined], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::NeedsDocuments => 'attention',
            self::Delivered, self::Approved, self::Hired => 'good',
            self::Closed, self::Cancelled, self::Declined => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Contacted => 'phone',
            self::Confirmed => 'check',
            self::Dispatched => 'truck',
            self::Delivered, self::Approved, self::Hired => 'check-circle',
            self::Reviewing => 'eye',
            self::NeedsDocuments => 'file',
            self::InDiscussion => 'message',
            self::Shortlisted => 'star',
            self::Interview => 'calendar',
            self::Closed => 'archive',
            self::Cancelled, self::Declined => 'x',
        };
    }
}
