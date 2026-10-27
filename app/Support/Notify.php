<?php

namespace App\Support;

use App\Enums\LeadType;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Emails the office and confirms to the customer. Mail goes out after the response
 * is sent, so a slow or failing mail server never makes a customer wait or lose a submission.
 *
 * Each request type can go to its own inbox (orders to the trade desk, applications to
 * credit control, CVs to HR); anything without its own inbox goes to PETERCO_NOTIFY_EMAIL.
 */
final class Notify
{
    /** @return list<string> */
    public static function recipients(?LeadType $type = null): array
    {
        return array_values(array_filter(self::configured($type), fn (string $a) => (bool) filter_var($a, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Configured entries that are not email addresses (typos), shown on the dashboard.
     *
     * @return list<string>
     */
    public static function invalid(): array
    {
        $all = array_merge(self::split((string) config('peterco.notify')), ...array_map(fn ($v) => self::split((string) $v), array_values((array) config('peterco.notify_by_type'))));

        return array_values(array_unique(array_filter($all, fn (string $a) => ! filter_var($a, FILTER_VALIDATE_EMAIL))));
    }

    /** @return list<string> */
    private static function configured(?LeadType $type): array
    {
        $own = $type ? self::split((string) config('peterco.notify_by_type.'.$type->value)) : [];

        return $own !== [] ? $own : self::split((string) config('peterco.notify'));
    }

    /** @return list<string> */
    private static function split(string $value): array
    {
        return preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function office(BaseNotification $notification, ?LeadType $type = null): void
    {
        $to = self::recipients($type);

        if ($to === []) {
            Log::warning('PETERCO_NOTIFY_EMAIL is not set; nobody was emailed about a new submission.', ['notification' => $notification::class]);

            return;
        }

        if ($skipped = self::invalid()) {
            Log::warning('A PETERCO_NOTIFY_* setting has entries that are not email addresses; they were skipped.', ['skipped' => $skipped]);
        }

        defer(function () use ($to, $notification): void {
            try {
                Notification::route('mail', $to)->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /** Confirmation to the customer, when they gave a usable email address. */
    public static function customer(?string $email, BaseNotification $notification): void
    {
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        defer(function () use ($email, $notification): void {
            try {
                Notification::route('mail', $email)->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
