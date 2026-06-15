<?php

declare(strict_types=1);

require_once __DIR__ . '/booking_notifications.php';
require_once __DIR__ . '/booking_guest_email_bodies.php';
require_once __DIR__ . '/booking_payment.php';

if (!function_exists('lh_booking_payment_checkout_url')) {
    function lh_booking_payment_checkout_url(PDO $pdo, array $booking): string
    {
        $stored = trim((string) ($booking['payment_checkout_url'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $checkoutId = trim((string) ($booking['maib_checkout_id'] ?? ''));
        if ($checkoutId === '') {
            return '';
        }

        if (!function_exists('lh_maib_get_checkout')) {
            require_once __DIR__ . '/maib_client.php';
        }
        if (!function_exists('lh_maib_configured') || !lh_maib_configured()) {
            return '';
        }

        try {
            $result = lh_maib_get_checkout($checkoutId);

            return trim((string) ($result->checkoutUrl ?? ''));
        } catch (Throwable $e) {
            error_log('payment_reminder checkout url: ' . $e->getMessage());

            return '';
        }
    }
}

if (!function_exists('lh_booking_payment_reminder_remaining_minutes')) {
    function lh_booking_payment_reminder_remaining_minutes(array $booking): int
    {
        $expires = trim((string) ($booking['payment_expires_at'] ?? ''));
        if ($expires !== '') {
            try {
                $exp = new DateTimeImmutable($expires);
                $remaining = (int) ceil(($exp->getTimestamp() - time()) / 60);

                return max(1, $remaining);
            } catch (Throwable) {
                /* fall through */
            }
        }

        $ttl = lh_booking_pending_ttl_minutes();
        $after = lh_booking_payment_reminder_after_minutes();

        return max(1, $ttl - $after);
    }
}

/**
 * @param array<string,mixed> $row Booking + property columns from cron SELECT
 * @return array{result: 'sent'|'skipped'|'error', reason?: string}
 */
function lh_payment_reminder_send_for_booking_row(PDO $pdo, array $row, array $opts = []): array
{
    $logCtx = (string) ($opts['log_context'] ?? 'payment_reminder');
    $bookingId = (int) ($row['id'] ?? $row['booking_id'] ?? 0);
    if ($bookingId < 1) {
        return ['result' => 'error', 'reason' => 'missing_booking_id'];
    }

    if (($row['status'] ?? '') !== 'pending'
        || ($row['payment_method'] ?? '') !== 'online'
        || ($row['payment_status'] ?? '') !== 'pending') {
        return ['result' => 'skipped', 'reason' => 'not_pending'];
    }

    $guestEmail = trim((string) ($row['guest_email'] ?? ''));
    if ($guestEmail === '' || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        return ['result' => 'skipped', 'reason' => 'invalid_guest_email'];
    }

    $expiresRaw = trim((string) ($row['payment_expires_at'] ?? ''));
    if ($expiresRaw === '' || !lh_booking_payment_expires_in_future($pdo, $expiresRaw)) {
        return ['result' => 'skipped', 'reason' => 'expired'];
    }

    $checkoutUrl = lh_booking_payment_checkout_url($pdo, $row);
    if ($checkoutUrl === '') {
        error_log("{$logCtx}: missing checkout url booking #{$bookingId}");

        return ['result' => 'error', 'reason' => 'missing_checkout_url'];
    }

    $bookingLocale = trim((string) ($row['locale'] ?? ''));
    if ($bookingLocale === '') {
        $bookingLocale = function_exists('lh_default_locale') ? lh_default_locale() : 'ro';
    }

    $property = $row;
    $propertyTitle = (string) ($row['title'] ?? ('Property #' . (int) ($row['property_id'] ?? 0)));
    if (function_exists('lh_property_apply_locale')) {
        $property = lh_property_apply_locale($property, $pdo, $bookingLocale);
        $propertyTitle = (string) ($property['title'] ?? $propertyTitle);
    }

    $remainingMinutes = lh_booking_payment_remaining_minutes($pdo, $expiresRaw);
    $guestBodyCtx = [
        'guest_name' => (string) ($row['guest_name'] ?? ''),
        'property_title' => $propertyTitle,
        'check_in' => (string) ($row['check_in'] ?? ''),
        'check_out' => (string) ($row['check_out'] ?? ''),
        'guests' => (int) ($row['guests'] ?? 0),
        'total_price' => (float) ($row['total_price'] ?? 0),
        'booking_id' => $bookingId,
        'locale' => $bookingLocale,
        'checkout_url' => $checkoutUrl,
        'payment_due_amount' => (float) ($row['payment_due_amount'] ?? $row['total_price'] ?? 0),
        'ttl_minutes' => $remainingMinutes,
        'payment_expires_at' => $expiresRaw,
        'is_reminder' => true,
    ];

    $couponCode = $row['coupon_code'] ?? null;
    $couponDiscount = (float) ($row['coupon_discount_amount'] ?? 0);
    if ($couponDiscount > 0.004 && $couponCode !== null && trim((string) $couponCode) !== '') {
        $guestBodyCtx['coupon_code'] = (string) $couponCode;
        $guestBodyCtx['coupon_discount_amount'] = $couponDiscount;
    }
    if ((float) ($row['online_discount_amount'] ?? 0) > 0.004) {
        $guestBodyCtx['online_discount_amount'] = (float) $row['online_discount_amount'];
    }

    $adminEmail = lh_booking_resolve_admin_notification_email();
    $clientSubject = lh_translate('email.pending_reminder_subject', ['minutes' => (string) $remainingMinutes], $bookingLocale);
    $clientMessage = lh_build_guest_booking_pending_payment_body($guestBodyCtx);

    $sent = send_booking_notification($guestEmail, $clientSubject, $clientMessage, $adminEmail);
    if (!$sent) {
        error_log("{$logCtx}: send failed booking #{$bookingId}");

        return ['result' => 'error', 'reason' => 'send_failed'];
    }

    if (lh_bookings_has_column($pdo, 'payment_reminder_sent_at')) {
        $upd = $pdo->prepare('UPDATE bookings SET payment_reminder_sent_at = NOW() WHERE id = :id AND payment_reminder_sent_at IS NULL');
        $upd->execute(['id' => $bookingId]);
    }

    return ['result' => 'sent'];
}

if (!function_exists('lh_php_function_disabled')) {
    function lh_php_function_disabled(string $function): bool
    {
        if (!function_exists($function)) {
            return true;
        }
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        return in_array($function, $disabled, true);
    }
}

if (!function_exists('lh_schedule_booking_payment_reminder')) {
    /** Spawn a background process that sends the guest payment reminder after the configured delay. */
    function lh_schedule_booking_payment_reminder(int $bookingId): void
    {
        if ($bookingId < 1 || PHP_OS_FAMILY === 'Windows' || lh_php_function_disabled('exec')) {
            return;
        }

        $delayMinutes = lh_booking_payment_reminder_after_minutes();
        $php = defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== ''
            ? PHP_BINARY
            : 'php';
        $artisan = function_exists('base_path')
            ? base_path('artisan')
            : dirname(__DIR__, 3) . '/artisan';
        $logFile = function_exists('storage_path')
            ? storage_path('logs/payment-reminder.log')
            : dirname(__DIR__, 3) . '/storage/logs/payment-reminder.log';

        $cmd = sprintf(
            '(sleep %d && %s %s bookings:send-payment-reminder-for %d) >> %s 2>&1 &',
            $delayMinutes * 60,
            escapeshellarg($php),
            escapeshellarg($artisan),
            $bookingId,
            escapeshellarg($logFile)
        );
        exec($cmd);
    }
}
