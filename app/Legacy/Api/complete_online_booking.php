<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/maib_client.php';
require_once dirname(__DIR__) . '/includes/booking_confirm.php';

/**
 * @return array{status: int, body: array<string, mixed>}
 */
function lh_api_complete_online_booking_fail(string $message, int $code = 400): array
{
    return [
        'status' => $code,
        'body' => ['success' => false, 'message' => $message],
    ];
}

/**
 * @return array{status: int, body: array<string, mixed>}
 */
function lh_api_complete_online_booking(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return lh_api_complete_online_booking_fail('Method not allowed', 405);
    }

    if (!lh_csrf_verify_post()) {
        return lh_api_complete_online_booking_fail('Invalid session', 403);
    }

    $checkoutId = trim((string) ($_POST['checkout_id'] ?? ''));
    $orderId = trim((string) ($_POST['order_id'] ?? ''));

    if ($checkoutId === '' && $orderId === '') {
        return lh_api_complete_online_booking_fail('Missing checkout reference');
    }

    try {
        if (!lh_maib_configured()) {
            return lh_api_complete_online_booking_fail('Payment gateway not configured', 503);
        }

        $pdo = getPDO();
        $bookingId = 0;

        if (preg_match('/^LH-(\d+)$/i', $orderId, $m)) {
            $bookingId = (int) $m[1];
        }

        if ($bookingId <= 0 && $checkoutId !== '') {
            $stmt = $pdo->prepare('SELECT id FROM bookings WHERE maib_checkout_id = ? LIMIT 1');
            $stmt->execute([$checkoutId]);
            $bookingId = (int) ($stmt->fetchColumn() ?: 0);
        }

        if ($bookingId <= 0) {
            return lh_api_complete_online_booking_fail('Booking not found', 404);
        }

        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            return lh_api_complete_online_booking_fail('Booking not found', 404);
        }

        if (($booking['status'] ?? '') === 'confirmed' && ($booking['payment_status'] ?? '') === 'paid') {
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'confirmed' => true,
                    'booking_id' => $bookingId,
                ],
            ];
        }

        if ($checkoutId === '') {
            $checkoutId = trim((string) ($booking['maib_checkout_id'] ?? ''));
        }
        if ($checkoutId === '') {
            return lh_api_complete_online_booking_fail('Checkout ID missing');
        }

        $checkout = lh_maib_get_checkout($checkoutId);
        $status = (string) ($checkout->status ?? '');

        if (strcasecmp($status, 'Completed') === 0) {
            $paymentId = null;
            $amount = (float) ($booking['payment_due_amount'] ?? $booking['total_price'] ?? 0);

            if (isset($checkout->payments) && is_array($checkout->payments) && count($checkout->payments) > 0) {
                $pay = $checkout->payments[0];
                $paymentId = (string) ($pay->id ?? $pay->paymentId ?? '');
                if (isset($pay->amount)) {
                    $amount = (float) $pay->amount;
                }
            } elseif (isset($checkout->payment) && is_object($checkout->payment)) {
                $pay = $checkout->payment;
                $paymentId = (string) ($pay->paymentId ?? $pay->id ?? '');
                if (isset($pay->amount)) {
                    $amount = (float) $pay->amount;
                }
            }

            lh_booking_confirm_after_online_payment($pdo, $bookingId, $checkoutId, $paymentId, $amount);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'confirmed' => true,
                    'booking_id' => $bookingId,
                    'checkout_id' => $checkoutId,
                ],
            ];
        }

        if (in_array($status, ['Failed', 'Cancelled', 'Expired', 'Abandoned'], true)) {
            lh_booking_fail_online_payment($pdo, $bookingId);

            return lh_api_complete_online_booking_fail('Payment not completed');
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'confirmed' => false,
                'status' => $status,
                'booking_id' => $bookingId,
            ],
        ];
    } catch (Throwable $e) {
        error_log('complete_online_booking: ' . $e->getMessage());

        return lh_api_complete_online_booking_fail('Server error', 500);
    }
}
