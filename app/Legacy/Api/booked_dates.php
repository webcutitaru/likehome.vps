<?php

declare(strict_types=1);

/**
 * @return array{status: int, body: array<string, mixed>}
 */
function lh_api_get_booked_dates(int $propertyId): array
{
    if ($propertyId < 1) {
        return [
            'status' => 400,
            'body' => ['success' => false, 'error' => lh_translate('api.property_id_invalid')],
        ];
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('
            SELECT DISTINCT start_date, end_date
            FROM   blocked_dates
            WHERE  property_id = :pid
              AND  end_date >= CURDATE()
            ORDER  BY start_date ASC
        ');
        $stmt->execute([':pid' => $propertyId]);
        $intervals = $stmt->fetchAll();

        $blocked_ranges = [];
        foreach ($intervals as $row) {
            $blocked_ranges[] = [
                'from' => $row['start_date'],
                'to' => $row['end_date'],
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'blocked_ranges' => $blocked_ranges,
            ],
        ];
    } catch (Exception $e) {
        error_log('get_booked_dates error: ' . $e->getMessage());

        return [
            'status' => 500,
            'body' => ['success' => false, 'error' => lh_translate('api.server_error')],
        ];
    }
}
