<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use App\Support\WebUrls;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    public function success(Request $request): View
    {
        LegacyBridge::boot();

        $checkoutId = trim((string) $request->query('checkoutId', ''));
        $orderId = trim((string) $request->query('orderId', ''));
        $booking = null;
        $pdo = LegacyBridge::pdo();

        if (preg_match('/^LH-(\d+)$/i', $orderId, $m)) {
            $stmt = $pdo->prepare(
                'SELECT b.*, p.title AS property_title FROM bookings b
                 LEFT JOIN properties p ON p.id = b.property_id WHERE b.id = ? LIMIT 1'
            );
            $stmt->execute([(int) $m[1]]);
            $booking = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } elseif ($checkoutId !== '') {
            $stmt = $pdo->prepare(
                'SELECT b.*, p.title AS property_title FROM bookings b
                 LEFT JOIN properties p ON p.id = b.property_id WHERE b.maib_checkout_id = ? LIMIT 1'
            );
            $stmt->execute([$checkoutId]);
            $booking = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        }

        $paidAtDisplay = '';
        if ($booking && ! empty($booking['paid_at'])) {
            try {
                $paidAtDisplay = (new \DateTimeImmutable((string) $booking['paid_at']))->format('d.m.Y H:i');
            } catch (\Throwable) {
                $paidAtDisplay = (string) $booking['paid_at'];
            }
        }

        $needsPolling = $booking
            && (($booking['status'] ?? '') !== 'confirmed' || ($booking['payment_status'] ?? '') !== 'paid');

        return view('pages.booking.success', [
            'pageTitle' => __('payment.success.title'),
            'pageDescription' => __('payment.success.description'),
            'canonicalUrl' => WebUrls::page('booking.success'),
            'robotsMeta' => 'noindex, nofollow',
            'booking' => $booking,
            'checkoutId' => $checkoutId,
            'orderId' => $orderId,
            'checkoutStatus' => trim((string) $request->query('checkoutStatus', '')),
            'paidAtDisplay' => $paidAtDisplay,
            'siteOrigin' => lh_public_site_origin(),
            'needsPolling' => $needsPolling,
            'csrfToken' => lh_csrf_token(),
            'completeUrl' => url('/api/booking/complete'),
        ]);
    }

    public function failed(Request $request): View
    {
        return view('pages.booking.failed', [
            'pageTitle' => __('payment.failed.title'),
            'pageDescription' => __('payment.failed.description'),
            'canonicalUrl' => WebUrls::page('booking.failed'),
            'robotsMeta' => 'noindex, nofollow',
            'checkoutId' => trim((string) $request->query('checkoutId', '')),
            'orderId' => trim((string) $request->query('orderId', '')),
        ]);
    }
}
