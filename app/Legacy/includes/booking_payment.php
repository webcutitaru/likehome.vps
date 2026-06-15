<?php

declare(strict_types=1);

if (!function_exists('lh_bookings_has_payment_columns')) {
    function lh_bookings_has_payment_columns(PDO $pdo): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'payment_method'");
            $has = $stmt !== false && $stmt->fetch() !== false;
        } catch (Throwable) {
            $has = false;
        }

        return $has;
    }
}

if (!function_exists('lh_booking_online_discount_percent')) {
    function lh_booking_online_discount_percent(): float
    {
        $v = (float) lh_env('BOOKING_ONLINE_DISCOUNT_PERCENT', '10');

        return max(0.0, min(100.0, $v));
    }
}

if (!function_exists('lh_booking_cancellation_refund_hours')) {
    function lh_booking_cancellation_refund_hours(): int
    {
        return max(0, (int) lh_env('BOOKING_CANCELLATION_REFUND_HOURS', '24'));
    }
}

if (!function_exists('lh_booking_pending_ttl_minutes')) {
    function lh_booking_pending_ttl_minutes(): int
    {
        return max(5, (int) lh_env('MAIB_PENDING_TTL_MINUTES', '30'));
    }
}

if (!function_exists('lh_booking_payment_reminder_after_minutes')) {
    /** Minutes after booking creation before sending the guest payment reminder email. */
    function lh_booking_payment_reminder_after_minutes(): int
    {
        $after = max(1, (int) lh_env('MAIB_PAYMENT_REMINDER_AFTER_MINUTES', '15'));
        $ttl = lh_booking_pending_ttl_minutes();

        return min($after, max(1, $ttl - 1));
    }
}

if (!function_exists('lh_booking_payment_expires_in_future')) {
    function lh_booking_payment_expires_in_future(PDO $pdo, string $expiresAt): bool
    {
        $expiresAt = trim($expiresAt);
        if ($expiresAt === '') {
            return false;
        }
        try {
            $stmt = $pdo->prepare('SELECT ? > NOW() AS ok');
            $stmt->execute([$expiresAt]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (bool) ($row['ok'] ?? false);
        } catch (Throwable) {
            return false;
        }
    }
}

if (!function_exists('lh_booking_payment_remaining_minutes')) {
    function lh_booking_payment_remaining_minutes(PDO $pdo, string $expiresAt): int
    {
        $expiresAt = trim($expiresAt);
        if ($expiresAt === '') {
            return max(1, lh_booking_pending_ttl_minutes() - lh_booking_payment_reminder_after_minutes());
        }
        try {
            $stmt = $pdo->prepare('SELECT GREATEST(1, CEIL(TIMESTAMPDIFF(MINUTE, NOW(), ?))) AS minutes');
            $stmt->execute([$expiresAt]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return max(1, (int) ($row['minutes'] ?? 1));
        } catch (Throwable) {
            return max(1, lh_booking_pending_ttl_minutes() - lh_booking_payment_reminder_after_minutes());
        }
    }
}

if (!function_exists('lh_bookings_has_column')) {
    function lh_bookings_has_column(PDO $pdo, string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE " . $pdo->quote($column));
            $cache[$column] = $stmt !== false && $stmt->fetch() !== false;
        } catch (Throwable) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}

if (!function_exists('lh_booking_payment_method_valid')) {
    function lh_booking_payment_method_valid(string $method): bool
    {
        return in_array($method, ['on_site', 'online'], true);
    }
}

/**
 * @return array{
 *   on_site_total: float,
 *   online_total: float,
 *   online_discount_percent: float,
 *   online_discount_amount: float,
 *   payment_due_amount: float
 * }
 */
if (!function_exists('lh_booking_payment_totals')) {
    function lh_booking_payment_totals(float $subtotalAfterCoupon): array
    {
        $onSite = max(0.0, round($subtotalAfterCoupon, 2));
        $pct = lh_booking_online_discount_percent();
        $discount = $pct > 0.004
            ? round($onSite * $pct / 100, 2)
            : 0.0;
        $online = max(0.0, round($onSite - $discount, 2));

        return [
            'on_site_total' => $onSite,
            'online_total' => $online,
            'online_discount_percent' => $pct,
            'online_discount_amount' => $discount,
            'payment_due_amount' => $online,
        ];
    }
}

if (!function_exists('lh_booking_eligible_for_full_refund')) {
    function lh_booking_eligible_for_full_refund(array $booking, ?DateTimeImmutable $now = null): bool
    {
        if (($booking['payment_method'] ?? '') !== 'online') {
            return false;
        }
        if (($booking['payment_status'] ?? '') !== 'paid') {
            return false;
        }
        if (trim((string) ($booking['maib_payment_id'] ?? '')) === '') {
            return false;
        }
        if (($booking['status'] ?? '') === 'cancelled') {
            return false;
        }

        $checkIn = (string) ($booking['check_in'] ?? '');
        if ($checkIn === '') {
            return false;
        }

        $now = $now ?? new DateTimeImmutable('now');
        $checkInDt = DateTimeImmutable::createFromFormat('Y-m-d', $checkIn);
        if (!$checkInDt || $checkInDt->format('Y-m-d') !== $checkIn) {
            return false;
        }

        $hours = lh_booking_cancellation_refund_hours();
        $deadline = $checkInDt->setTime(0, 0, 0);
        $cutoff = $deadline->modify('-' . $hours . ' hours');

        return $now < $cutoff;
    }
}

if (!function_exists('lh_booking_payment_status_label')) {
    function lh_booking_payment_status_label(string $status): string
    {
        return match ($status) {
            'pay_at_property' => 'Plată la check-in',
            'pending' => 'Plată în așteptare',
            'paid' => 'Plătit online',
            'failed' => 'Plată eșuată',
            'refunded' => 'Rambursat',
            'partial_refund' => 'Ramburs parțial',
            default => $status,
        };
    }
}

if (!function_exists('lh_bookings_has_refunded_amount_column')) {
    function lh_bookings_has_refunded_amount_column(PDO $pdo): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'refunded_amount'");
            $has = $stmt !== false && $stmt->fetch() !== false;
        } catch (Throwable) {
            $has = false;
        }

        return $has;
    }
}

if (!function_exists('lh_booking_paid_amount')) {
    function lh_booking_paid_amount(array $booking): float
    {
        $paid = (float) ($booking['payment_amount'] ?? 0);
        if ($paid > 0.004) {
            return round($paid, 2);
        }

        return round((float) ($booking['payment_due_amount'] ?? 0), 2);
    }
}

if (!function_exists('lh_booking_refunded_amount')) {
    function lh_booking_refunded_amount(array $booking): float
    {
        return round(max(0.0, (float) ($booking['refunded_amount'] ?? 0)), 2);
    }
}

if (!function_exists('lh_booking_refundable_amount')) {
    function lh_booking_refundable_amount(array $booking): float
    {
        return round(max(0.0, lh_booking_paid_amount($booking) - lh_booking_refunded_amount($booking)), 2);
    }
}

if (!function_exists('lh_booking_can_refund')) {
    function lh_booking_can_refund(array $booking): bool
    {
        if (($booking['payment_method'] ?? '') !== 'online') {
            return false;
        }
        $status = (string) ($booking['payment_status'] ?? '');
        if (!in_array($status, ['paid', 'partial_refund'], true)) {
            return false;
        }
        if (trim((string) ($booking['maib_payment_id'] ?? '')) === '') {
            return false;
        }

        return lh_booking_refundable_amount($booking) > 0.004;
    }
}

if (!function_exists('lh_booking_below_standard_refund_window')) {
    /** True when cancellation is under BOOKING_CANCELLATION_REFUND_HOURS before check-in (UI warning only). */
    function lh_booking_below_standard_refund_window(array $booking, ?DateTimeImmutable $now = null): bool
    {
        return !lh_booking_eligible_for_full_refund($booking, $now);
    }
}
