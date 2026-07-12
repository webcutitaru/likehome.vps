<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;
use App\Models\Property;
use App\Support\GallerySaveRuntime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PropertySaveService
{
    /**
     * @param  array<string, mixed>  $formData
     * @param  list<UploadedFile|string>  $newImages
     * @return array{ok: true, debug_timings?: array<string, float|int>}|array{ok: false, error: string}
     */
    public function save(Property $property, array $formData, array $newImages = []): array
    {
        LegacyBridge::boot();
        $this->loadLegacyIncludes();

        $propertyId = (int) $property->id;
        if ($propertyId <= 0) {
            return ['ok' => false, 'error' => 'ID invalid.'];
        }

        $post = $this->toLegacyPost($formData);

        $validationError = lh_edit_property_validate_post($post);
        if ($validationError !== null) {
            return ['ok' => false, 'error' => $validationError];
        }

        $existingImages = $post['existing_images'] ?? [];
        if (! is_array($existingImages)) {
            $existingImages = [];
        }

        if ($newImages !== []) {
            GallerySaveRuntime::begin();
        }

        $checkpointEvery = GallerySaveRuntime::checkpointEvery();
        $sinceCheckpoint = 0;

        foreach ($newImages as $image) {
            $stored = $this->storeGalleryImage($image, $propertyId);
            if ($stored === null) {
                continue;
            }

            $existingImages[] = $stored;
            $sinceCheckpoint++;

            if ($sinceCheckpoint >= $checkpointEvery) {
                $existingImages = array_values(array_unique(array_filter(array_map(
                    static fn ($name) => trim((string) $name),
                    $existingImages
                ))));
                GallerySaveRuntime::applyMySqlSessionTimeouts();
                $this->persistGalleryImageNames($propertyId, $existingImages);
                $sinceCheckpoint = 0;
            }
        }

        $post['existing_images'] = array_values(array_unique(array_filter(array_map(
            static fn ($name) => trim((string) $name),
            $existingImages
        ))));

        $post['save_property'] = '1';
        $post['property_id'] = (string) $propertyId;

        lh_legacy_refresh_db_connections();
        $this->persistGalleryImageNames($propertyId, $post['existing_images']);

        $result = lh_edit_property_save_from_post(getConn(), LegacyBridge::pdo(), $propertyId, $post, []);

        if (! ($result['ok'] ?? false)) {
            return $result;
        }

        $this->syncManualBlocks($propertyId, $formData);

        return $result;
    }

    private function loadLegacyIncludes(): void
    {
        $base = base_path('app/Legacy/includes');
        $files = [
            'lh_property_translations_save.php',
            'ical_importer.php',
            'lh_edit_property_save_core.php',
        ];

        foreach ($files as $file) {
            $path = $base.'/'.$file;
            if (is_readable($path)) {
                require_once $path;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    public function toLegacyPost(array $formData): array
    {
        $post = $formData;

        $post['amenities'] = $formData['amenities'] ?? [];

        $post['existing_images'] = $formData['existing_images'] ?? [];

        $periods = $formData['pricing_periods'] ?? [];
        if (is_array($periods)) {
            $post['pp_label'] = [];
            $post['pp_date_start'] = [];
            $post['pp_date_end'] = [];
            $post['pp_price'] = [];
            $post['pp_price_weekend'] = [];
            $post['pp_min_stay'] = [];
            $post['pp_stay_discounts_json'] = [];

            foreach ($periods as $period) {
                if (! is_array($period)) {
                    continue;
                }
                $post['pp_label'][] = (string) ($period['label'] ?? '');
                $post['pp_date_start'][] = lh_calendar_ymd($period['date_start'] ?? '');
                $post['pp_date_end'][] = lh_calendar_ymd($period['date_end'] ?? '');
                $post['pp_price'][] = (string) ($period['price'] ?? '');
                $post['pp_price_weekend'][] = (string) ($period['price_weekend'] ?? '');
                $post['pp_min_stay'][] = (string) ($period['min_stay'] ?? '');

                $sd = $period['stay_discounts'] ?? [];
                $sdRules = [];
                if (is_array($sd)) {
                    foreach ($sd as $rule) {
                        if (! is_array($rule)) {
                            continue;
                        }
                        $mn = (int) ($rule['min_nights'] ?? 0);
                        $val = (float) ($rule['value'] ?? 0);
                        $unit = ($rule['unit'] ?? 'percent') === 'fixed_stay' ? 'fixed_stay' : 'percent';
                        if ($mn >= 1 && $val > 0) {
                            $sdRules[] = ['min_nights' => $mn, 'value' => $val, 'unit' => $unit];
                        }
                    }
                }
                $post['pp_stay_discounts_json'][] = json_encode($sdRules, JSON_UNESCAPED_UNICODE);
            }
        }

        $globalSd = $formData['stay_discounts_global'] ?? [];
        if (is_array($globalSd)) {
            $post['g_sd_min'] = [];
            $post['g_sd_val'] = [];
            $post['g_sd_unit'] = [];
            foreach ($globalSd as $rule) {
                if (! is_array($rule)) {
                    continue;
                }
                $post['g_sd_min'][] = (string) ($rule['min_nights'] ?? '');
                $post['g_sd_val'][] = (string) ($rule['value'] ?? '');
                $post['g_sd_unit'][] = ($rule['unit'] ?? 'percent') === 'fixed_stay' ? 'fixed_stay' : 'percent';
            }
        }

        foreach (['en', 'ru'] as $locale) {
            $post['tr_'.$locale.'_title'] = (string) ($formData['tr_'.$locale.'_title'] ?? '');
            $post['tr_'.$locale.'_slug'] = (string) ($formData['tr_'.$locale.'_slug'] ?? '');
            $post['tr_'.$locale.'_description_long'] = (string) ($formData['tr_'.$locale.'_description_long'] ?? '');
        }

        return $post;
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    private function syncManualBlocks(int $propertyId, array $formData): void
    {
        $pdo = LegacyBridge::pdo();
        $conn = getConn();

        $deleted = $formData['manual_blocks_deleted'] ?? [];
        if (is_array($deleted)) {
            foreach ($deleted as $blockId) {
                $id = (int) $blockId;
                if ($id <= 0) {
                    continue;
                }
                $stmt = $pdo->prepare("DELETE FROM blocked_dates WHERE id = ? AND property_id = ? AND source = 'manual_block'");
                $stmt->execute([$id, $propertyId]);
                if ($stmt->rowCount() > 0) {
                    lh_admin_log_activity($conn, 'manual_block_delete', 'property', $propertyId, ['blocked_date_id' => $id]);
                }
            }
        }

        $newBlocks = $formData['manual_blocks_new'] ?? [];
        if (! is_array($newBlocks)) {
            return;
        }

        foreach ($newBlocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $start = trim(lh_calendar_ymd($block['start_date'] ?? ''));
            $end = trim(lh_calendar_ymd($block['end_date'] ?? ''));
            $note = trim((string) ($block['notes'] ?? ''));
            if ($start === '' || $end === '' || $end <= $start) {
                continue;
            }
            $eid = 'manual-'.bin2hex(random_bytes(8));
            $ins = $pdo->prepare(
                'INSERT INTO blocked_dates (property_id, start_date, end_date, source, external_event_id, notes) VALUES (?, ?, ?, \'manual_block\', ?, ?)'
            );
            $ins->execute([$propertyId, $start, $end, $eid, $note !== '' ? $note : null]);
            lh_admin_log_activity($conn, 'manual_block_add', 'property', $propertyId, [
                'start' => $start,
                'end' => $end,
                'note' => $note !== '' ? $note : null,
            ]);
        }
    }

    public function storeUploadedImageForProperty(UploadedFile|string $image, int $propertyId): ?string
    {
        return $this->storeGalleryImage($image, $propertyId);
    }

    private function storeGalleryImage(UploadedFile|string $image, int $propertyId): ?string
    {
        $path = $image instanceof UploadedFile
            ? $image->getRealPath()
            : Storage::disk('public')->path($image);

        if ($path === false || $path === '' || ! is_readable($path)) {
            return null;
        }

        $this->ensureGalleryDirectories($propertyId);

        $basename = lh_store_property_image([
            'name' => $image instanceof UploadedFile ? $image->getClientOriginalName() : basename($path),
            'type' => $image instanceof UploadedFile ? (string) $image->getMimeType() : (string) mime_content_type($path),
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($path),
        ], $propertyId);

        if ($basename === null) {
            return null;
        }

        $this->mirrorToPublicUploads($propertyId, $basename);

        return $basename;
    }

    private function ensureGalleryDirectories(int $propertyId): void
    {
        $storageDir = storage_path('app/public/uploads/properties/'.$propertyId);
        if (! is_dir($storageDir) && ! mkdir($storageDir, 0755, true) && ! is_dir($storageDir)) {
            throw new RuntimeException('Cannot create gallery directory.');
        }

        $legacyDir = base_path('app/Legacy/uploads/properties/'.$propertyId);
        if (! is_dir($legacyDir) && ! mkdir($legacyDir, 0755, true) && ! is_dir($legacyDir)) {
            throw new RuntimeException('Cannot create legacy gallery directory.');
        }
    }

    /**
     * @param  list<string>  $imageNames
     */
    private function persistGalleryImageNames(int $propertyId, array $imageNames): void
    {
        if ($imageNames === []) {
            return;
        }

        DB::table('properties')
            ->where('id', $propertyId)
            ->update(['image_name' => implode(',', $imageNames)]);
    }

    private function mirrorToPublicUploads(int $propertyId, string $basename): void
    {
        $legacyDir = base_path('app/Legacy/uploads/properties/'.$propertyId);
        $storageDir = storage_path('app/public/uploads/properties/'.$propertyId);
        $publicDir = public_path('uploads/properties/'.$propertyId);

        foreach ([$storageDir, $publicDir] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $sourceMain = $legacyDir.DIRECTORY_SEPARATOR.$basename;
        if (! is_file($sourceMain)) {
            return;
        }

        foreach ([$storageDir, $publicDir] as $targetDir) {
            $dest = $targetDir.DIRECTORY_SEPARATOR.$basename;
            if (! is_file($dest)) {
                copy($sourceMain, $dest);
            }
            $thumb = lh_property_image_thumb_basename($basename);
            if ($thumb !== '') {
                $thumbSource = $legacyDir.DIRECTORY_SEPARATOR.$thumb;
                $thumbDest = $targetDir.DIRECTORY_SEPARATOR.$thumb;
                if (is_file($thumbSource) && ! is_file($thumbDest)) {
                    copy($thumbSource, $thumbDest);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function loadFormData(Property $property): array
    {
        LegacyBridge::boot();
        $this->loadLegacyIncludes();

        $data = $property->toArray();
        $data['amenities'] = json_decode((string) ($property->amenities ?? '[]'), true) ?: [];
        $imageNames = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($property->image_name ?? ''))
        )));
        $data['existing_images'] = array_map(
            static fn (string $basename): array => ['basename' => $basename],
            $imageNames
        );

        $translations = lh_property_translations_load(LegacyBridge::pdo(), (int) $property->id);
        foreach (['en', 'ru'] as $locale) {
            $tr = $translations[$locale] ?? [];
            $data['tr_'.$locale.'_title'] = (string) ($tr['title'] ?? '');
            $data['tr_'.$locale.'_slug'] = (string) ($tr['slug'] ?? '');
            $data['tr_'.$locale.'_description_long'] = (string) ($tr['description_long'] ?? '');
        }

        $periods = lh_property_pricing_periods_load((int) $property->id);
        $data['pricing_periods'] = array_map(static function (array $period): array {
            return [
                'label' => (string) ($period['label'] ?? ''),
                'date_start' => (string) ($period['date_start'] ?? ''),
                'date_end' => (string) ($period['date_end'] ?? ''),
                'price' => (string) ($period['price'] ?? ''),
                'price_weekend' => isset($period['price_weekend']) && (float) $period['price_weekend'] > 0
                    ? (string) $period['price_weekend']
                    : '',
                'min_stay' => isset($period['min_stay']) && (int) ($period['min_stay'] ?? 0) >= 1
                    ? (string) $period['min_stay']
                    : '',
                'stay_discounts' => array_map(static fn (array $sd): array => [
                    'min_nights' => (int) ($sd['min_nights'] ?? 0),
                    'value' => (string) ($sd['value'] ?? ''),
                    'unit' => (string) ($sd['unit'] ?? 'percent'),
                ], $period['stay_discounts'] ?? []),
            ];
        }, $periods);

        $globalSd = lh_property_stay_discounts_load_by_property((int) $property->id)['global'];
        $data['stay_discounts_global'] = array_map(static fn (array $sd): array => [
            'min_nights' => (int) ($sd['min_nights'] ?? 0),
            'value' => (string) ($sd['value'] ?? ''),
            'unit' => (string) ($sd['unit'] ?? 'percent'),
        ], $globalSd);

        $stmt = LegacyBridge::pdo()->prepare(
            "SELECT id, start_date, end_date, notes FROM blocked_dates WHERE property_id = ? AND source = 'manual_block' ORDER BY start_date ASC"
        );
        $stmt->execute([(int) $property->id]);
        $data['manual_blocks'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $data['manual_blocks_new'] = [];
        $data['manual_blocks_deleted'] = [];

        if (empty($property->ical_export_token)) {
            $token = bin2hex(random_bytes(16));
            DB::table('properties')->where('id', $property->id)->update(['ical_export_token' => $token]);
            $data['ical_export_token'] = $token;
        }

        $icalToken = (string) ($data['ical_export_token'] ?? $property->ical_export_token);
        $data['ical_export_url'] = url('/ical/'.rawurlencode($icalToken).'.ics');

        return $data;
    }
}
