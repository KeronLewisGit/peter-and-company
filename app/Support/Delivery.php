<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Delivery days a customer may ask for on an order request: weekdays from the next working
 * day up to peterco.delivery.days_ahead ahead, minus public holidays. A request, not a
 * booking: the trade desk confirms the actual day by phone.
 */
final class Delivery
{
    /** @return list<Carbon> */
    public static function days(): array
    {
        $days = [];
        $day = today()->addDay();
        $last = today()->addDays((int) config('peterco.delivery.days_ahead', 14));
        for (; $day->lte($last); $day = $day->copy()->addDay()) {
            if (self::isWorkingDay($day)) {
                $days[] = $day->copy();
            }
        }

        return $days;
    }

    public static function isWorkingDay(Carbon $day): bool
    {
        return in_array($day->dayOfWeekIso, (array) config('peterco.delivery.weekdays', [1, 2, 3, 4, 5]), true)
            && ! in_array($day->toDateString(), (array) config('peterco.closed_dates', []), true);
    }

    public static function isRequestable(string $date): bool
    {
        return in_array($date, array_map(fn (Carbon $d) => $d->toDateString(), self::days()), true);
    }
}
