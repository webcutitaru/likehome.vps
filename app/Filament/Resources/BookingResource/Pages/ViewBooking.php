<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingAdminService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Editează')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn (Booking $record): bool => $record->status !== 'cancelled')
                ->fillForm(fn (Booking $record): array => [
                    'guest_name' => $record->guest_name,
                    'guest_email' => $record->guest_email,
                    'guest_phone' => $record->guest_phone,
                    'check_in' => $record->check_in?->format('Y-m-d'),
                    'check_out' => $record->check_out?->format('Y-m-d'),
                    'guests' => $record->guests,
                ])
                ->schema([
                    TextInput::make('guest_name')->label('Nume')->required(),
                    TextInput::make('guest_email')->label('Email')->email()->required(),
                    TextInput::make('guest_phone')->label('Telefon')->required(),
                    DatePicker::make('check_in')->label('Check-in')->required()->native(false),
                    DatePicker::make('check_out')->label('Check-out')->required()->native(false),
                    TextInput::make('guests')->label('Oaspeți')->numeric()->minValue(1)->required(),
                ])
                ->action(function (Booking $record, array $data, BookingAdminService $service): void {
                    $result = $service->updateBooking($record, $data, auth()->id());

                    Notification::make()
                        ->title($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();

                    if ($result['ok']) {
                        $this->record->refresh();
                        $this->refreshFormData([
                            'guest_name', 'guest_email', 'guest_phone',
                            'check_in', 'check_out', 'guests', 'total_price', 'status',
                        ]);
                    }
                }),
            Action::make('confirm')
                ->label('Confirmă')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Booking $record): bool => ! in_array($record->status, ['confirmed', 'cancelled'], true))
                ->requiresConfirmation()
                ->modalHeading('Confirmă rezervarea')
                ->modalDescription('Rezervarea va fi marcată ca confirmată și datele vor fi blocate în calendar.')
                ->action(function (Booking $record, BookingAdminService $service): void {
                    $result = $service->confirm($record, auth()->id());

                    Notification::make()
                        ->title($result['message'])
                        ->{$result['ok'] ? 'success' : 'warning'}()
                        ->send();

                    if ($result['ok']) {
                        $this->record->refresh();
                        $this->refreshFormData(['status']);
                    }
                }),
            Action::make('refund')
                ->label('Rambursare maib')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('warning')
                ->visible(fn (Booking $record): bool => $record->status !== 'cancelled'
                    && in_array($record->payment_method, ['maib', 'online', 'card'], true)
                    && (float) ($record->payment_amount ?? 0) > 0)
                ->schema([
                    TextInput::make('refund_amount')
                        ->label('Sumă parțială')
                        ->helperText('Lasă gol pentru rambursare integrală')
                        ->numeric(),
                    TextInput::make('refund_reason')->label('Motiv (opțional)'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Rambursare maib')
                ->action(function (Booking $record, array $data, BookingAdminService $service): void {
                    $amountRaw = trim((string) ($data['refund_amount'] ?? ''));
                    $amount = $amountRaw === '' ? null : (float) str_replace(',', '.', $amountRaw);
                    $reason = trim((string) ($data['refund_reason'] ?? ''));

                    $result = $service->refund(
                        $record,
                        $amount,
                        $reason !== '' ? $reason : null,
                        auth()->id(),
                    );

                    Notification::make()
                        ->title($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();

                    if ($result['ok']) {
                        $this->record->refresh();
                        $this->refreshFormData([
                            'payment_status', 'refunded_amount', 'payment_amount', 'paid_at',
                        ]);
                    }
                }),
            Action::make('cancel')
                ->label('Anulează')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Booking $record): bool => $record->status !== 'cancelled')
                ->requiresConfirmation()
                ->modalHeading('Anulează rezervarea')
                ->modalDescription('Rezervarea va fi anulată. Rambursarea nu se face automat.')
                ->action(function (Booking $record, BookingAdminService $service): void {
                    $result = $service->cancel($record, auth()->id());

                    Notification::make()
                        ->title($result['message'])
                        ->{$result['ok'] ? 'warning' : 'danger'}()
                        ->send();

                    if ($result['ok']) {
                        $this->record->refresh();
                        $this->refreshFormData(['status']);
                    }
                }),
            Action::make('send_reminder')
                ->label('Trimite reminder')
                ->icon(Heroicon::OutlinedEnvelope)
                ->visible(fn (Booking $record): bool => $record->status === 'confirmed'
                    && ($record->check_out?->toDateString() ?? '') >= now()->toDateString())
                ->requiresConfirmation()
                ->modalHeading('Trimite reminder check-in')
                ->modalDescription('Se trimite emailul „Înainte de sosire” către oaspete.')
                ->action(function (Booking $record, BookingAdminService $service): void {
                    $result = $service->sendCheckinReminder($record, auth()->id());

                    Notification::make()
                        ->title($result['message'])
                        ->{$result['ok'] ? 'success' : 'warning'}()
                        ->send();

                    if ($result['ok']) {
                        $this->record->refresh();
                        $this->refreshFormData(['checkin_reminder_sent_at']);
                    }
                }),
        ];
    }
}
