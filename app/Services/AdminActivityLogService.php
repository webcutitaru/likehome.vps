<?php

namespace App\Services;

use App\Legacy\LegacyBridge;

final class AdminActivityLogService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $details = null,
        ?int $actorUserId = null,
        bool $anonymous = false,
    ): void {
        LegacyBridge::boot();

        lh_admin_log_activity(
            getConn(),
            $action,
            $entityType,
            $entityId,
            $details,
            $actorUserId,
            $anonymous,
        );
    }
}
