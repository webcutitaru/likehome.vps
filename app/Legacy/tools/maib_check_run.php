<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/booking_payment.php';
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
