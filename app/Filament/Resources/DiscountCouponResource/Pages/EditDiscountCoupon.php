<?php

namespace App\Filament\Resources\DiscountCouponResource\Pages;

use App\Filament\Resources\DiscountCouponResource;
use App\Models\DiscountCoupon;
use App\Services\DiscountCouponService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EditDiscountCoupon extends EditRecord
{
    protected static string $resource = DiscountCouponResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var DiscountCoupon $record */
        $record = $this->getRecord();

        $data['property_ids'] = $record->properties()->pluck('properties.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(DiscountCouponService::class)->save($data, $record);
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw $e;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Cuponul a fost salvat.';
    }
}
