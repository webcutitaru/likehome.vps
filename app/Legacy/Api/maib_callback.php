<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/maib_client.php';
require_once dirname(__DIR__) . '/includes/booking_confirm.php';

/**
 * @param array<string, string|null> $headers
 * @return array{status: int, body: string, content_type?: string}
 */
function lh_api_maib_callback(string $rawBody, array $headers): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return [
            'status' => 405,
            'body' => json_encode(['success' => false, 'message' => 'Method not allowed']),
            'content_type' => 'application/json; charset=utf-8',
        ];
    }

    $norm = [];
    foreach ($headers as $k => $v) {
        $norm[strtolower((string) $k)] = $v;
    }

    $xSignature = isset($norm['x-signature']) ? (string) $norm['x-signature'] : null;
    $xTimestamp = isset($norm['x-signature-timestamp']) ? (string) $norm['x-signature-timestamp'] : null;

    if (!lh_maib_verify_callback_signature($rawBody, $xSignature, $xTimestamp)) {
        error_log('maib_callback: invalid signature');

        return [
            'status' => 401,
            'body' => 'Invalid signature',
        ];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        return [
            'status' => 400,
            'body' => 'Invalid JSON',
        ];
    }

    $checkoutId = trim((string) ($data['checkoutId'] ?? ''));
    $paymentStatus = trim((string) ($data['paymentStatus'] ?? ''));
    $paymentId = trim((string) ($data['paymentId'] ?? ''));
    $orderId = trim((string) ($data['orderId'] ?? ''));
    $paymentAmount = (float) ($data['paymentAmount'] ?? $data['amount'] ?? 0);

    $bookingId = 0;
    if (preg_match('/^LH-(\d+)$/i', $orderId, $m)) {
        $bookingId = (int) $m[1];
    }

    try {
        $pdo = getPDO();

        if ($bookingId <= 0 && $checkoutId !== '') {
            $stmt = $pdo->prepare('SELECT id FROM bookings WHERE maib_checkout_id = ? LIMIT 1');
            $stmt->execute([$checkoutId]);
            $bookingId = (int) ($stmt->fetchColumn() ?: 0);
        }

        if ($bookingId <= 0) {
            error_log('maib_callback: booking not found for orderId=' . $orderId . ' checkoutId=' . $checkoutId);

            return [
                'status' => 200,
                'body' => 'OK',
            ];
        }

        if (strcasecmp($paymentStatus, 'Executed') === 0) {
            lh_booking_confirm_after_online_payment($pdo, $bookingId, $checkoutId, $paymentId !== '' ? $paymentId : null, $paymentAmount);
        } elseif (in_array(strtolower($paymentStatus), ['failed', 'cancelled', 'canceled'], true)) {
            lh_booking_fail_online_payment($pdo, $bookingId);
        }
    } catch (Throwable $e) {
        error_log('maib_callback error: ' . $e->getMessage());
    }

    return [
        'status' => 200,
        'body' => 'OK',
    ];
}
