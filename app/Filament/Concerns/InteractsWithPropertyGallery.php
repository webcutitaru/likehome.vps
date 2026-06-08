<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

trait InteractsWithPropertyGallery
{
    /**
     * @param  list<string>  $order
     */
    public function updateExistingImagesOrder(array $order): void
    {
        $this->data['existing_images'] = array_values(array_map(
            static fn (string $basename): array => ['basename' => $basename],
            array_values(array_filter($order, static fn ($basename): bool => is_string($basename) && trim($basename) !== ''))
        ));
    }

    public function removeExistingImage(string $basename): void
    {
        $basename = trim($basename);

        $this->data['existing_images'] = array_values(array_filter(
            $this->data['existing_images'] ?? [],
            static function ($row) use ($basename): bool {
                $name = is_array($row) ? trim((string) ($row['basename'] ?? '')) : trim((string) $row);

                return $name !== $basename;
            }
        ));
    }

    /**
     * @return list<array{basename: string}>
     */
    protected function normalizeExistingImagesForSave(array $rows): array
    {
        return array_values(array_filter(array_map(
            static function ($row): string {
                if (is_array($row)) {
                    return trim((string) ($row['basename'] ?? ''));
                }

                return trim((string) $row);
            },
            $rows
        )));
    }
}
