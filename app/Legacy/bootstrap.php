<?php

declare(strict_types=1);

/**
 * Global legacy bootstrap (no namespace) so lh_* helpers and includes
 * are registered in the global namespace for ported PHP includes.
 */
function lh_legacy_bootstrap(): void
{
    static $booted = false;

    if ($booted) {
        return;
    }

    $booted = true;

    if (! function_exists('lh_env')) {
        function lh_env(string $key, string $default = ''): string
        {
            $fromConfig = config("likehome.env.{$key}");
            if ($fromConfig !== null && $fromConfig !== '') {
                return (string) $fromConfig;
            }

            if (function_exists('env')) {
                $fromEnv = env($key);
                if (is_string($fromEnv) && $fromEnv !== '') {
                    return $fromEnv;
                }
            }

            $v = getenv($key);
            if ($v === false && isset($_ENV[$key]) && is_string($_ENV[$key])) {
                $v = $_ENV[$key];
            }
            if ($v !== false && $v !== '') {
                return (string) $v;
            }

            return $default;
        }
    }

    if (! function_exists('getPDO')) {
        function getPDO(): PDO
        {
            return Illuminate\Support\Facades\DB::connection()->getPdo();
        }
    }

    if (! function_exists('getConn')) {
        function getConn(): mysqli
        {
            global $conn;

            if ($conn instanceof mysqli) {
                return $conn;
            }

            $conn = App\Legacy\LegacyBridge::createMysqliConnection();

            return $conn;
        }
    }

    if (! function_exists('lh_legacy_refresh_db_connections')) {
        /**
         * Drop stale MySQL handles after long-running work (e.g. gallery batch upload).
         */
        function lh_legacy_refresh_db_connections(): void
        {
            global $conn;

            if ($conn instanceof mysqli) {
                @mysqli_close($conn);
                $conn = null;
            }

            Illuminate\Support\Facades\DB::reconnect();

            $waitSeconds = 600;
            if (function_exists('config')) {
                $waitSeconds = max(60, (int) config('likehome.gallery_save.db_wait_timeout_seconds', 600));
            }

            try {
                $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
                $pdo->exec('SET SESSION wait_timeout='.$waitSeconds);
                $pdo->exec('SET SESSION interactive_timeout='.$waitSeconds);
            } catch (\Throwable) {
                // Best-effort; reconnect alone still helps.
            }

            $conn = App\Legacy\LegacyBridge::createMysqliConnection();
            if ($conn instanceof mysqli) {
                @mysqli_query($conn, 'SET SESSION wait_timeout='.$waitSeconds);
                @mysqli_query($conn, 'SET SESSION interactive_timeout='.$waitSeconds);
            }
        }
    }

    if (! function_exists('lh_public_url')) {
        function lh_public_url(string $path = ''): string
        {
            $path = trim($path);
            $base = defined('SITE_BASE_PATH') ? SITE_BASE_PATH : '';

            if ($path === '' || $path === '/') {
                return $base === '' ? '/' : $base.'/';
            }

            if (isset($path[0]) && $path[0] === '#') {
                return ($base === '' ? '/' : $base.'/').$path;
            }

            return ($base === '' ? '' : $base).'/'.ltrim($path, '/');
        }
    }

    if (! function_exists('lh_translation_file_path')) {
        function lh_translation_file_path(string $locale): ?string
        {
            $path = base_path('lang/'.$locale.'.php');

            return is_readable($path) ? $path : null;
        }
    }

    if (! function_exists('lh_page_content_file')) {
        function lh_page_content_file(string $page, ?string $locale = null): string
        {
            $locale = $locale ?? app()->getLocale();
            $base = base_path('lang/pages/'.$page.'.'.$locale.'.php');
            if (is_readable($base)) {
                return $base;
            }

            $fallback = base_path('lang/pages/'.$page.'.ro.php');

            return is_readable($fallback) ? $fallback : $base;
        }
    }

    if (! defined('DB_HOST')) {
        define('DB_HOST', lh_env('DB_HOST', 'localhost'));
    }
    if (! defined('DB_NAME')) {
        define('DB_NAME', lh_env('DB_NAME', 'likehome_db'));
    }
    if (! defined('DB_USER')) {
        define('DB_USER', lh_env('DB_USER', 'root'));
    }
    if (! defined('DB_PASS')) {
        define('DB_PASS', lh_env('DB_PASS', ''));
    }
    if (! defined('DB_CHARSET')) {
        define('DB_CHARSET', lh_env('DB_CHARSET', 'utf8mb4'));
    }

    if (! defined('LH_PROJECT_ROOT')) {
        define('LH_PROJECT_ROOT', base_path());
    }

    $siteBaseRaw = lh_env('SITE_BASE_PATH', '');
    if ($siteBaseRaw === '' || $siteBaseRaw === '/') {
        if (! defined('SITE_BASE_PATH')) {
            define('SITE_BASE_PATH', '');
        }
    } elseif (! defined('SITE_BASE_PATH')) {
        define('SITE_BASE_PATH', rtrim($siteBaseRaw, '/'));
    }

    date_default_timezone_set(lh_env('APP_TIMEZONE', 'Europe/Chisinau'));

    $isCli = PHP_SAPI === 'cli';
    if (! $isCli && session_status() === PHP_SESSION_NONE) {
        $secureCookie = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    if (! defined('ADMIN_NOTIFICATION_EMAIL')) {
        define('ADMIN_NOTIFICATION_EMAIL', lh_env('ADMIN_NOTIFICATION_EMAIL'));
    }
    if (! defined('TELEGRAM_BOT_TOKEN')) {
        define('TELEGRAM_BOT_TOKEN', lh_env('TELEGRAM_BOT_TOKEN'));
    }
    if (! defined('TELEGRAM_CHAT_ID')) {
        define('TELEGRAM_CHAT_ID', lh_env('TELEGRAM_CHAT_ID'));
    }
    if (! defined('BOOKING_MAIL_FROM')) {
        define('BOOKING_MAIL_FROM', lh_env('BOOKING_MAIL_FROM'));
    }

    $mailjetKey = trim(lh_env('MAILJET_API_KEY'));
    $mailjetSecret = trim(lh_env('MAILJET_API_SECRET'));
    $mailjetReady = $mailjetKey !== ''
        && $mailjetSecret !== ''
        && class_exists(Mailjet\Client::class);

    if (! defined('MAILJET_API_KEY')) {
        define('MAILJET_API_KEY', $mailjetKey);
    }
    if (! defined('MAILJET_API_SECRET')) {
        define('MAILJET_API_SECRET', $mailjetSecret);
    }
    if (! defined('MAILJET_READY')) {
        define('MAILJET_READY', $mailjetReady);
    }
    if (! defined('MAIL_SMTP_READY')) {
        define('MAIL_SMTP_READY', false);
    }
    if (! defined('BOOKING_GUEST_SUPPORT_PHONES')) {
        define(
            'BOOKING_GUEST_SUPPORT_PHONES',
            lh_env('BOOKING_GUEST_SUPPORT_PHONES', "Andrei — +373 69 397 372\nAurel — +373 69 111 427")
        );
    }

    $base = __DIR__.'/includes';

    $files = [
        'csrf.php',
        'admin_activity_log.php',
        'mysqli_stmt_compat.php',
        'locale.php',
        'site_nav.php',
        'location_labels.php',
        'property_i18n.php',
        'property_amenity_catalog.php',
        'i18n_js.php',
        'booking_locale.php',
        'page_i18n.php',
        'seo.php',
        'currency.php',
        'company_legal.php',
        'booking_payment.php',
        'booking_pricing.php',
        'coupons.php',
        'upload_image.php',
        'calendar_helpers.php',
        'booking_admin.php',
        'security_headers.php',
    ];

    foreach ($files as $file) {
        $path = $base.'/'.$file;
        if (is_readable($path)) {
            require_once $path;
        }
    }

    if (PHP_SAPI !== 'cli' && function_exists('lh_send_security_headers')) {
        lh_send_security_headers();
    }
}
