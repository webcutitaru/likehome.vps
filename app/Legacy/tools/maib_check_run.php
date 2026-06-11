<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/booking_payment.php';
require_once __DIR__.'/../includes/booking_notifications.php';
require_once __DIR__.'/../includes/maib_client.php';

function lh_maib_check_line(string $label, string $value): void
{
    echo str_pad($label.': ', 22).$value."\n";
}

echo "Like HOME — maib diagnostic\n";
echo str_repeat('=', 44)."\n\n";

$envFile = function_exists('lh_find_env_file') ? lh_find_env_file() : null;
lh_maib_check_line('Env file', $envFile ?? '(not found — .env not loaded)');
lh_maib_check_line('APP_ENV', lh_env('APP_ENV', '(unset)'));
lh_maib_check_line('SITE_BASE_PATH', defined('SITE_BASE_PATH') ? (SITE_BASE_PATH === '' ? '/' : SITE_BASE_PATH) : '?');
lh_maib_check_line('PUBLIC_SITE_URL', lh_public_site_origin());
lh_maib_check_line('MAIB_API_BASE', lh_maib_api_base_url() ?? '(empty → production api.maibmerchants.md/v2/)');
lh_maib_check_line('MAIB configured', lh_maib_configured() ? 'yes' : 'NO');
lh_maib_check_line('MAIB_CLIENT_ID', trim(lh_env('MAIB_CLIENT_ID', '')) !== '' ? 'set ('.strlen(trim(lh_env('MAIB_CLIENT_ID', ''))).' chars)' : 'MISSING');
lh_maib_check_line('MAIB_CLIENT_SECRET', trim(lh_env('MAIB_CLIENT_SECRET', '')) !== '' ? 'set' : 'MISSING');
lh_maib_check_line('MAIB_SIGNATURE_KEY', trim(lh_env('MAIB_SIGNATURE_KEY', '')) !== '' ? 'set' : 'MISSING');
lh_maib_check_line('Vendor SDK', class_exists(\MaibEcomm\MaibCheckoutSdk\MaibCheckoutApiRequest::class) ? 'OK' : 'MISSING — vendor/maib-ecomm');
lh_maib_check_line('curl', function_exists('curl_init') ? 'OK' : 'MISSING');
lh_maib_check_line('openssl', extension_loaded('openssl') ? 'OK' : 'MISSING');

echo "\n--- Notifications ---\n";
lh_maib_check_line('MAILJET_READY', (defined('MAILJET_READY') && MAILJET_READY) ? 'yes' : 'NO');
lh_maib_check_line('BOOKING_MAIL_FROM', (defined('BOOKING_MAIL_FROM') && trim((string) BOOKING_MAIL_FROM) !== '') ? (string) BOOKING_MAIL_FROM : 'MISSING');
$adminEmail = lh_booking_resolve_admin_notification_email();
lh_maib_check_line('ADMIN email', $adminEmail !== '' ? $adminEmail : 'MISSING');
lh_maib_check_line('TELEGRAM token', (defined('TELEGRAM_BOT_TOKEN') && trim((string) TELEGRAM_BOT_TOKEN) !== '') ? 'set' : 'MISSING');
lh_maib_check_line('TELEGRAM chat', (defined('TELEGRAM_CHAT_ID') && trim((string) TELEGRAM_CHAT_ID) !== '') ? (string) TELEGRAM_CHAT_ID : 'MISSING');
lh_maib_check_line('allow_url_fopen', ini_get('allow_url_fopen') ? 'On' : 'Off');
$createBookingPath = function_exists('base_path')
    ? base_path('app/Legacy/Api/create_booking.php')
    : dirname(__DIR__, 3).'/app/Legacy/Api/create_booking.php';
$createBookingSrc = is_readable($createBookingPath) ? (string) file_get_contents($createBookingPath) : '';
lh_maib_check_line('pending email code', str_contains($createBookingSrc, 'lh_booking_send_pending_payment_notifications') ? 'yes' : 'NO — git pull needed');

$sendTest = isset($_GET['send']) && (string) $_GET['send'] === '1';
if ($sendTest) {
    echo "\n--- Send test (?send=1) ---\n";
    if ($adminEmail === '') {
        lh_maib_check_line('email test', 'SKIP — no admin email');
    } else {
        $ts = date('Y-m-d H:i:s');
        $emailOk = send_booking_notification($adminEmail, '[TEST maib_check] LikeHome '.$ts, 'Test email from maib_check diagnostic.');
        lh_maib_check_line('email test', $emailOk ? 'OK' : 'FAILED — see error_log');
    }
    $tgToken = defined('TELEGRAM_BOT_TOKEN') ? trim((string) TELEGRAM_BOT_TOKEN) : '';
    $tgChat = defined('TELEGRAM_CHAT_ID') ? trim((string) TELEGRAM_CHAT_ID) : '';
    if ($tgToken === '' || $tgChat === '') {
        lh_maib_check_line('telegram test', 'SKIP — not configured');
    } else {
        $tgOk = send_telegram_notification($tgToken, $tgChat, '⏳ [TEST maib_check] '.date('Y-m-d H:i:s'));
        lh_maib_check_line('telegram test', $tgOk ? 'OK' : 'FAILED — see error_log');
    }
}

try {
    $pdo = getPDO();
    lh_maib_check_line('DB', 'OK');
    lh_maib_check_line('payment_method col', lh_bookings_has_payment_columns($pdo) ? 'yes' : 'NO — run migrations/003');
    lh_maib_check_line('refunded_amount col', lh_bookings_has_refunded_amount_column($pdo) ? 'yes' : 'NO — run migrations/004');
} catch (Throwable $e) {
    lh_maib_check_line('DB', 'FAIL — '.$e->getMessage());
}

$urls = lh_maib_payment_urls('en');
echo "\nCheckout URLs (sent to maib):\n";
lh_maib_check_line('  callbackUrl', $urls['callbackUrl']);
lh_maib_check_line('  successUrl', $urls['successUrl']);
lh_maib_check_line('  failUrl', $urls['failUrl']);

echo "\n--- Step 0: raw HTTP to maib auth/token (bypass SDK) ---\n";
$baseUrl = lh_maib_api_base_url() ?? 'https://api.maibmerchants.md/v2/';
$tokenUrl = rtrim($baseUrl, '/').'/auth/token';
$rawPayload = json_encode([
    'clientId' => trim(lh_env('MAIB_CLIENT_ID', '')),
    'clientSecret' => trim(lh_env('MAIB_CLIENT_SECRET', '')),
], JSON_UNESCAPED_UNICODE);

$rawBody = '';
$rawCode = 0;
$rawErr = '';
if (function_exists('curl_init')) {
    $ch = curl_init($tokenUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $rawPayload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 45,
    ]);
    $rawBody = (string) curl_exec($ch);
    $rawCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $rawErr = curl_error($ch);
    }
    curl_close($ch);
}

lh_maib_check_line('POST URL', $tokenUrl);
lh_maib_check_line('HTTP status', $rawErr !== '' ? 'curl error: '.$rawErr : (string) $rawCode);
$preview = trim($rawBody);
if ($preview === '') {
    echo "Response body: (empty)\n";
} else {
    echo "Response body (first 800 chars):\n".substr($preview, 0, 800).(strlen($preview) > 800 ? "\n…" : '')."\n";
}

echo "\n--- Step 1: auth token (SDK) ---\n";
try {
    $token = lh_maib_access_token();
    lh_maib_check_line('Result', 'OK — token '.strlen($token).' chars');
} catch (Throwable $e) {
    lh_maib_check_line('Result', 'FAIL');
    echo "\n".$e->getMessage()."\n";
    if ($e->getPrevious()) {
        echo 'Previous: '.$e->getPrevious()->getMessage()."\n";
    }
    echo "\nLikely causes when Step 0 body is not maib JSON with ok/errors:\n";
    echo "  • hosting firewall blocks outbound HTTPS to sandbox.maibmerchants.md\n";
    echo "  • proxy/WAF returns HTML or generic JSON instead of maib API\n";
    echo "  • ask host to whitelist outbound :443 to sandbox.maibmerchants.md\n";

    return;
}

echo "\n--- Step 2: createCheckout (1.00 MDL, same shape as booking) ---\n";
$payload = [
    'amount' => 1.0,
    'currency' => lh_currency_code(),
    'callbackUrl' => $urls['callbackUrl'],
    'successUrl' => $urls['successUrl'],
    'failUrl' => $urls['failUrl'],
    'language' => lh_maib_checkout_language('en'),
    'orderInfo' => [
        'id' => 'LH-DIAG-'.time(),
        'description' => 'Like HOME diagnostic checkout',
        'date' => (new DateTimeImmutable('now'))->format('c'),
        'orderAmount' => 1.0,
        'orderCurrency' => lh_currency_code(),
    ],
    'payerInfo' => [
        'name' => 'Diagnostic Test',
        'email' => 'test@likehome.md',
        'phone' => '491701234567',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'userAgent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'LikeHomeMaibCheck/1.0'), 0, 512),
    ],
];

try {
    $checkout = lh_maib_create_checkout($payload);
    lh_maib_check_line('Result', 'OK');
    lh_maib_check_line('checkoutId', $checkout->checkoutId);
    lh_maib_check_line('checkoutUrl', $checkout->checkoutUrl);
    echo "\nIf both steps OK, maib works — booking error is elsewhere (amount 0, phone empty, etc.).\n";
} catch (Throwable $e) {
    lh_maib_check_line('Result', 'FAIL');
    echo "\n".$e->getMessage()."\n";
    if ($e->getPrevious()) {
        echo 'Previous: '.$e->getPrevious()->getMessage()."\n";
    }
    echo "\nThis is the same failure as \"Could not start payment\" on booking.\n";
}

echo "\nRoute: /maib_check.php?key=… (Laravel legacy compat)\n";
