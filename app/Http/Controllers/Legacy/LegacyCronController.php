<?php

declare(strict_types=1);

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

final class LegacyCronController extends Controller
{
    public function icalSync(Request $request): Response
    {
        $this->authorizeCron($request, 'ICAL_SYNC_SECRET');

        LegacyBridge::boot();
        Artisan::call('ical:sync');

        return $this->plainResponse(Artisan::output());
    }

    public function checkinReminder(Request $request): Response
    {
        $this->authorizeCron($request, 'CHECKIN_REMINDER_SECRET');

        LegacyBridge::boot();
        Artisan::call('bookings:send-checkin-reminders');

        return $this->plainResponse(Artisan::output());
    }

    public function expirePending(Request $request): Response
    {
        $this->authorizeCron($request, 'MAIB_PENDING_CRON_SECRET');

        LegacyBridge::boot();
        Artisan::call('bookings:expire-pending');

        return $this->plainResponse(Artisan::output());
    }

    public function paymentReminder(Request $request): Response
    {
        $this->authorizeCron($request, 'MAIB_PENDING_CRON_SECRET');

        LegacyBridge::boot();
        Artisan::call('bookings:send-payment-reminders');

        return $this->plainResponse(Artisan::output());
    }

    private function authorizeCron(Request $request, string $envKey): void
    {
        LegacyBridge::boot();

        $secret = trim((string) lh_env($envKey, ''));
        $key = trim((string) $request->query('key', ''));

        if ($secret === '') {
            abort(503, $envKey.' is not set in .env');
        }

        if (! hash_equals($secret, $key)) {
            abort(403, 'Forbidden — invalid or missing ?key=');
        }
    }

    private function plainResponse(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
