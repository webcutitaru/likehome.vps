<?php

namespace App\Filament\Resources\DiscountCouponResource\Pages;

use App\Filament\Resources\DiscountCouponResource;
use App\Services\DiscountCouponService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CreateDiscountCoupon extends CreateRecord
{
    protected static string $resource = DiscountCouponResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(DiscountCouponService::class)->save($data);
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw $e;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cuponul a fost salvat.';
    }
}
