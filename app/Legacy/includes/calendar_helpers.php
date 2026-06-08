<?php

declare(strict_types=1);

if (! function_exists('lh_calendar_ymd')) {
    function lh_calendar_ymd(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = (string) $value;

        return strlen($string) >= 10 ? substr($string, 0, 10) : $string;
    }
}

if (! function_exists('lh_calendar_night_blocked')) {
    /**
     * @param  list<array{start_date:string,end_date:string}>  $blocks
     */
    function lh_calendar_night_blocked(string $ymd, array $blocks): bool
    {
        foreach ($blocks as $b) {
            $s = lh_calendar_ymd($b['start_date'] ?? '');
            $e = lh_calendar_ymd($b['end_date'] ?? '');
            if (strlen($s) === 10 && strlen($e) === 10 && $s <= $ymd && $ymd < $e) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('lh_calendar_booking_for_night')) {
    /**
     * @param  list<array<string,mixed>>  $bookings
     * @return array<string,mixed>|null
     */
    function lh_calendar_booking_for_night(string $ymd, array $bookings): ?array
    {
        foreach ($bookings as $b) {
            $ci = lh_calendar_ymd($b['check_in'] ?? '');
            $co = lh_calendar_ymd($b['check_out'] ?? '');
            if (strlen($ci) === 10 && strlen($co) === 10 && $ci <= $ymd && $ymd < $co) {
                return $b;
            }
        }

        return null;
    }
}

if (! function_exists('lh_calendar_blocked_cell_label')) {
    /**
     * @param  list<array<string,mixed>>  $blocks
     */
    function lh_calendar_blocked_cell_label(string $ymd, array $blocks): ?string
    {
        $hasIcal = false;
        $hasAny = false;
        foreach ($blocks as $b) {
            $s = lh_calendar_ymd($b['start_date'] ?? '');
            $e = lh_calendar_ymd($b['end_date'] ?? '');
            if (strlen($s) !== 10 || strlen($e) !== 10 || ! ($s <= $ymd && $ymd < $e)) {
                continue;
            }
            $hasAny = true;
            if (($b['source'] ?? '') === 'ical_import') {
                $hasIcal = true;
            }
        }
        if (! $hasAny) {
            return null;
        }

        return $hasIcal ? 'REALTY' : 'Blocat';
    }
}
