<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Services\AdminActivityLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['location'] = $data['city'] ?? '';
        $data['description'] = $data['title'] ?? '';
        $data['property_type'] = $data['property_type'] ?? 'Apartament';
        $data['rooms'] = $data['rooms'] ?? 1;
        $data['sleep_capacity'] = $data['sleep_capacity'] ?? 2;
        $data['guests_included'] = $data['guests_included'] ?? 2;
        $data['min_stay'] = $data['min_stay'] ?? 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(AdminActivityLogService::class)->log(
            'property_create',
            'property',
            (int) $this->record->id,
            ['title' => $this->record->title],
            auth()->id(),
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Proprietatea a fost creată.';
    }
}
