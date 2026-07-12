<?php

declare(strict_types=1);

namespace App\Services;

use App\Legacy\LegacyBridge;
use Illuminate\Support\Facades\DB;

final class PropertyGalleryRepairService
{
    /**
     * @return list<int>
     */
    public function findDesyncedPropertyIds(?int $onlyPropertyId = null): array
    {
        $query = DB::table('properties')
            ->select('id', 'image_name')
            ->orderBy('id');

        if ($onlyPropertyId !== null && $onlyPropertyId > 0) {
            $query->where('id', $onlyPropertyId);
        }

        $ids = [];

        foreach ($query->get() as $row) {
            $propertyId = (int) $row->id;
            if ($propertyId <= 0) {
                continue;
            }

            if (trim((string) ($row->image_name ?? '')) !== '') {
                continue;
            }

            if ($this->discoverGalleryBasenames($propertyId) === []) {
                continue;
            }

            $ids[] = $propertyId;
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    public function discoverGalleryBasenames(int $propertyId): array
    {
        if ($propertyId <= 0) {
            return [];
        }

        foreach ($this->gallerySourceDirs($propertyId) as $dir) {
            $basenames = $this->listMainWebpBasenames($dir);
            if ($basenames !== []) {
                return $basenames;
            }
        }

        return [];
    }

    /**
     * @return array{property_id: int, image_names: list<string>, csv: string, updated: bool, mirrored: int}
     */
    public function repair(int $propertyId, bool $dryRun = false, bool $mirrorLegacy = false): array
    {
        $imageNames = $this->discoverGalleryBasenames($propertyId);
        $csv = implode(',', $imageNames);
        $mirrored = 0;

        if ($mirrorLegacy && $imageNames !== []) {
            $mirrored = $this->mirrorToLegacy($propertyId, $imageNames, $dryRun);
        }

        $updated = false;

        if (! $dryRun && $imageNames !== []) {
            DB::table('properties')
                ->where('id', $propertyId)
                ->update(['image_name' => $csv]);
            $updated = true;
        }

        return [
            'property_id' => $propertyId,
            'image_names' => $imageNames,
            'csv' => $csv,
            'updated' => $updated,
            'mirrored' => $mirrored,
        ];
    }

    /**
     * @return list<string>
     */
    private function gallerySourceDirs(int $propertyId): array
    {
        return [
            public_path('uploads/properties/'.$propertyId),
            base_path('app/Legacy/uploads/properties/'.$propertyId),
        ];
    }

    /**
     * @return list<string>
     */
    private function listMainWebpBasenames(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/upload_image.php');

        $entries = [];

        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            if (! preg_match('/\.webp$/i', $name)) {
                continue;
            }

            if (preg_match('/_thumb\.webp$/i', $name)) {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (! is_file($path)) {
                continue;
            }

            $entries[] = [
                'basename' => $name,
                'mtime' => filemtime($path) ?: 0,
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            if ($a['mtime'] === $b['mtime']) {
                return strcmp($a['basename'], $b['basename']);
            }

            return $a['mtime'] <=> $b['mtime'];
        });

        return array_values(array_map(
            static fn (array $entry): string => $entry['basename'],
            $entries
        ));
    }

    /**
     * @param  list<string>  $imageNames
     */
    private function mirrorToLegacy(int $propertyId, array $imageNames, bool $dryRun): int
    {
        $publicDir = public_path('uploads/properties/'.$propertyId);
        $legacyDir = base_path('app/Legacy/uploads/properties/'.$propertyId);

        if (! is_dir($publicDir)) {
            return 0;
        }

        if (! $dryRun && ! is_dir($legacyDir) && ! mkdir($legacyDir, 0755, true) && ! is_dir($legacyDir)) {
            return 0;
        }

        LegacyBridge::boot();
        require_once base_path('app/Legacy/includes/upload_image.php');

        $copied = 0;

        foreach ($imageNames as $basename) {
            $source = $publicDir.DIRECTORY_SEPARATOR.$basename;
            if (! is_file($source)) {
                continue;
            }

            $dest = $legacyDir.DIRECTORY_SEPARATOR.$basename;
            if (! $dryRun && ! is_file($dest)) {
                copy($source, $dest);
                ++$copied;
            }

            $thumb = lh_property_image_thumb_basename($basename);
            if ($thumb === '') {
                continue;
            }

            $thumbSource = $publicDir.DIRECTORY_SEPARATOR.$thumb;
            $thumbDest = $legacyDir.DIRECTORY_SEPARATOR.$thumb;
            if (is_file($thumbSource) && ! $dryRun && ! is_file($thumbDest)) {
                copy($thumbSource, $thumbDest);
                ++$copied;
            }
        }

        return $copied;
    }
}
