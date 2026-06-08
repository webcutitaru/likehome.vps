<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\AdminActivityLogService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        /** @var User $record */
        $record = $this->getRecord();
        $data = $this->data;

        $wasAdmin = $record->role === 'admin';
        $newRole = (string) ($data['role'] ?? $record->role);
        $newStatus = (string) ($data['status'] ?? $record->status);
        $activeAdmins = User::countActiveAdmins();

        if ($wasAdmin && $newRole !== 'admin' && $activeAdmins === 1 && $record->status === 'active') {
            Notification::make()
                ->title('Nu poți schimba rolul singurului administrator activ.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'role' => 'Nu poți schimba rolul singurului administrator activ.',
            ]);
        }

        if ($wasAdmin && $newStatus === 'disabled' && $activeAdmins === 1 && $record->status === 'active') {
            Notification::make()
                ->title('Nu poți dezactiva singurul administrator activ.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'status' => 'Nu poți dezactiva singurul administrator activ.',
            ]);
        }
    }

    protected function afterSave(): void
    {
        app(AdminActivityLogService::class)->log(
            'user_update',
            'user',
            (int) $this->record->id,
            ['email' => $this->record->email],
            auth()->id(),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (User $record): void {
                    if ($record->role === 'admin' && User::countActiveAdmins() === 1 && $record->status === 'active') {
                        Notification::make()
                            ->title('Nu poți șterge singurul administrator activ.')
                            ->danger()
                            ->send();

                        $this->halt();
                    }
                })
                ->after(function (User $record): void {
                    app(AdminActivityLogService::class)->log(
                        'user_delete',
                        'user',
                        (int) $record->id,
                        ['email' => $record->email],
                        auth()->id(),
                    );
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Utilizatorul a fost actualizat.';
    }
}
