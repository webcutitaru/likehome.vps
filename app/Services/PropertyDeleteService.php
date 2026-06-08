<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;
use App\Models\AdminActivityLog;
use App\Models\Property;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PropertyDeleteService
{
    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function delete(Property $property, ?int $actorUserId = null): array
    {
        LegacyBridge::boot();

        $propertyId = (int) $property->id;
        if ($propertyId <= 0) {
            return ['ok' => false, 'error' => 'Proprietate invalidă.'];
        }

        try {
            DB::transaction(function () use ($property, $propertyId, $actorUserId): void {
                $property->delete();

                AdminActivityLog::query()->create([
                    'user_id' => $actorUserId,
                    'action' => 'property_delete',
                    'entity_type' => 'property',
                    'entity_id' => $propertyId,
                    'details' => json_encode(['title' => (string) $property->title], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);
            });

            $this->purgePropertyFiles($propertyId, $property);
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'error' => 'Ștergerea a eșuat. Încearcă din nou.'];
        }

        return ['ok' => true];
    }

    private function purgePropertyFiles(int $propertyId, Property $property): void
    {
        try {
            LegacyBridge::boot();

            $uploadsDir = base_path('app/Legacy/uploads/properties/'.$propertyId);
            if (function_exists('lh_remove_directory') && is_dir($uploadsDir)) {
                lh_remove_directory($uploadsDir);
            }

            foreach ($this->imageBasenames($property) as $basename) {
                if (function_exists('lh_delete_property_image_from_disk')) {
                    lh_delete_property_image_from_disk($propertyId, $basename);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return list<string>
     */
    private function imageBasenames(Property $property): array
    {
        return array_values(array_filter(array_map(
            static fn (string $name): string => trim($name),
            explode(',', (string) ($property->image_name ?? ''))
        ), static fn (string $name): bool => $name !== '' && strpbrk($name, "\\/") === false));
    }
}
