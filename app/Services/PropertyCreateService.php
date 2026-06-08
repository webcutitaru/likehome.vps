<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;
use App\Models\Property;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PropertyCreateService
{
    public function __construct(
        private readonly PropertySaveService $saveService,
        private readonly AdminActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     * @param  list<UploadedFile|string>  $newImages
     * @return array{ok: true, property_id: int}|array{ok: false, error: string}
     */
    public function create(array $formData, array $newImages = []): array
    {
        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/lh_add_property_core.php');

        $post = $this->saveService->toLegacyPost($formData);
        unset($post['existing_images'], $post['property_id'], $post['save_property'], $post['ical_export_url']);

        $conn = LegacyBridge::createMysqliConnection();

        try {
            $created = lh_add_property_create_from_post($conn, $post);
            if (! ($created['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'error' => (string) ($created['error'] ?? 'Crearea proprietății a eșuat.'),
                ];
            }

            $propertyId = (int) ($created['property_id'] ?? 0);
            if ($propertyId <= 0) {
                return ['ok' => false, 'error' => 'ID proprietate invalid după creare.'];
            }

            $uploaded = [];
            foreach ($newImages as $image) {
                $stored = $this->saveService->storeUploadedImageForProperty($image, $propertyId);
                if ($stored !== null) {
                    $uploaded[] = $stored;
                }
            }

            if ($uploaded !== []) {
                DB::table('properties')
                    ->where('id', $propertyId)
                    ->update(['image_name' => implode(',', $uploaded)]);
            }

            $this->activityLog->log(
                'property_create',
                'property',
                $propertyId,
                ['title' => (string) ($formData['title'] ?? '')],
                auth()->id(),
            );

            return ['ok' => true, 'property_id' => $propertyId];
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'error' => 'Crearea proprietății a eșuat. Încearcă din nou.'];
        } finally {
            mysqli_close($conn);
        }
    }
}
