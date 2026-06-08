<?php

declare(strict_types=1);

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MaibCheckController extends Controller
{
    public function __invoke(Request $request): Response
    {
        LegacyBridge::boot();

        $secret = lh_env('MAIB_PENDING_CRON_SECRET', lh_env('ICAL_SYNC_SECRET', ''));
        $key = trim((string) $request->query('key', ''));

        if ($secret === '' || ! hash_equals((string) $secret, $key)) {
            return response(
                "Forbidden — append ?key= from ICAL_SYNC_SECRET or MAIB_PENDING_CRON_SECRET\n",
                403,
                ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex, nofollow']
            );
        }

        ob_start();
        require base_path('app/Legacy/tools/maib_check_run.php');
        $body = (string) ob_get_clean();

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
