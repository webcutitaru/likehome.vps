<?php

declare(strict_types=1);

namespace App\Support;

final class GallerySaveRuntime
{
    public static function begin(): void
    {
        $seconds = max(120, (int) config('likehome.gallery_save.max_execution_seconds', 900));

        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }

        $memory = (string) config('likehome.gallery_save.memory_limit', '512M');
        if ($memory !== '') {
            @ini_set('memory_limit', $memory);
        }
    }

    public static function checkpointEvery(): int
    {
        return max(1, (int) config('likehome.gallery_save.checkpoint_every', 10));
    }

    public static function applyMySqlSessionTimeouts(): void
    {
        if (! function_exists('lh_legacy_refresh_db_connections')) {
            return;
        }

        lh_legacy_refresh_db_connections();
    }
}
