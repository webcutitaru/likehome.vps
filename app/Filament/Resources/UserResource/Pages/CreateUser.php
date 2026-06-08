<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\AdminActivityLogService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        app(AdminActivityLogService::class)->log(
            'user_create',
            'user',
            (int) $this->record->id,
            ['email' => $this->record->email],
            auth()->id(),
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Utilizatorul a fost creat.';
    }
}
