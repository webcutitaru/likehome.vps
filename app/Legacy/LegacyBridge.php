<?php

declare(strict_types=1);

namespace App\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mysqli;
use PDO;
use RuntimeException;

final class LegacyBridge
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        require_once __DIR__.'/bootstrap.php';
        lh_legacy_bootstrap();

        self::$booted = true;
    }

    public static function syncRequest(Request $request): void
    {
        self::boot();

        $_GET = array_merge($_GET, $request->query->all());
        $_POST = array_merge($_POST, $request->request->all());
        $_SERVER['REQUEST_METHOD'] = $request->method();
        $_SERVER['REMOTE_ADDR'] = $request->ip() ?? '0';
        $_SERVER['HTTP_USER_AGENT'] = substr($request->userAgent() ?? '', 0, 512);
        $_SERVER['CONTENT_LENGTH'] = (string) ($request->header('Content-Length') ?? '');
    }

    public static function createMysqliConnection(): mysqli
    {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (! $conn) {
            throw new RuntimeException('Legacy mysqli connection failed: '.mysqli_connect_error());
        }

        if (! mysqli_set_charset($conn, DB_CHARSET)) {
            throw new RuntimeException('Legacy mysqli charset failed: '.mysqli_error($conn));
        }

        return $conn;
    }

    public static function pdo(): PDO
    {
        self::boot();

        return DB::connection()->getPdo();
    }

    /**
     * @param  list<array<string, mixed>>  $properties
     * @return list<array<string, mixed>>
     */
    public static function applyLocaleList(array $properties): array
    {
        self::boot();

        return lh_property_apply_locale_list($properties, self::pdo(), app()->getLocale());
    }

    /**
     * @param  array<string, mixed>  $property
     * @return array<string, mixed>
     */
    public static function applyLocale(array $property): array
    {
        self::boot();

        return lh_property_apply_locale($property, self::pdo(), app()->getLocale());
    }

    public static function resolvePropertyBySlug(string $slug): ?array
    {
        self::boot();

        return lh_property_resolve_by_slug(self::pdo(), $slug);
    }
}
