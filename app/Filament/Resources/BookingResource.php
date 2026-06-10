<?php

namespace App\Filament\Resources;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Rezervari;

    protected static ?string $navigationLabel = 'Rezervări';

    protected static ?string $modelLabel = 'rezervare';

    protected static ?string $pluralModelLabel = 'rezervări';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rezervare')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')
                                ->label('ID')
                                ->badge(),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->state(fn (Booking $record): string => self::statusLabel($record)),
                            TextEntry::make('created_at')
                                ->label('Creată la')
                                ->dateTime('d.m.Y H:i'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('guest_name')->label('Nume'),
                            TextEntry::make('guest_phone')->label('Telefon'),
                            TextEntry::make('guest_email')->label('Email')->columnSpanFull(),
                            TextEntry::make('guests')->label('Oaspeți'),
                            TextEntry::make('locale')->label('Limbă'),
                        ]),
                    ]),
                Section::make('Sejur')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('property.title')->label('Proprietate'),
                            TextEntry::make('property.lot_id')->label('LOT'),
                            TextEntry::make('check_in')->label('Check-in')->date('d.m.Y'),
                            TextEntry::make('check_out')->label('Check-out')->date('d.m.Y'),
                            TextEntry::make('total_price')->label('Total')
                                ->formatStateUsing(fn ($state): string => self::formatMoney((float) $state)),
                            TextEntry::make('coupon_code')->label('Cupon')
                                ->placeholder('—'),
                            TextEntry::make('coupon_discount_amount')->label('Reducere cupon')
                                ->formatStateUsing(fn ($state): string => self::formatMoney((float) $state))
                                ->placeholder('—'),
                        ]),
                    ]),
                Section::make('Plată')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('payment_method')
                                ->label('Metodă')
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'online' => 'Plată online (maib)',
                                    'on_site' => 'Plată la check-in',
                                    default => $state ?: '—',
                                }),
                            TextEntry::make('payment_status')->label('Status plată')->placeholder('—'),
                            TextEntry::make('payment_amount')->label('Plătit')
                                ->formatStateUsing(fn ($state): string => self::formatMoney((float) $state)),
                            TextEntry::make('refunded_amount')->label('Rambursat')
                                ->formatStateUsing(fn ($state): string => self::formatMoney((float) $state)),
                            TextEntry::make('paid_at')->label('Plătit la')->dateTime('d.m.Y H:i')->placeholder('—'),
                            TextEntry::make('checkin_reminder_sent_at')->label('Reminder trimis')
                                ->dateTime('d.m.Y H:i')
                                ->placeholder('—'),
                            TextEntry::make('maib_checkout_id')
                                ->label('checkout_id (maib)')
                                ->placeholder('—')
                                ->copyable()
                                ->columnSpanFull()
                                ->visible(fn (Booking $record): bool => $record->payment_method === 'online'),
                            TextEntry::make('maib_payment_id')
                                ->label('payment_id (maib)')
                                ->placeholder('—')
                                ->copyable()
                                ->columnSpanFull()
                                ->visible(fn (Booking $record): bool => $record->payment_method === 'online'),
                            TextEntry::make('maib_refund_id')
                                ->label('refund_id (maib)')
                                ->placeholder('—')
                                ->copyable()
                                ->columnSpanFull()
                                ->visible(fn (Booking $record): bool => $record->payment_method === 'online'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('guest_name')
                    ->label('Oaspete')
                    ->searchable()
                    ->description(fn (Booking $record): string => (string) $record->guest_email),
                TextColumn::make('property.title')
                    ->label('Proprietate')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => self::formatMoney((float) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Booking $record): string => self::statusLabel($record))
                    ->color(fn (Booking $record): string => match ($record->status) {
                        'confirmed' => ($record->check_out?->toDateString() ?? '') < now()->toDateString() ? 'gray' : 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status_group')
                    ->label('Status')
                    ->options([
                        'all' => 'Toate',
                        'active' => 'Active',
                        'finished' => 'Finalizate',
                        'pending' => 'În așteptare',
                        'cancelled' => 'Anulate',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? 'all';

                        return match ($value) {
                            'active' => $query
                                ->where('status', 'confirmed')
                                ->whereDate('check_out', '>=', now()->toDateString()),
                            'finished' => $query
                                ->where('status', 'confirmed')
                                ->whereDate('check_out', '<', now()->toDateString()),
                            'pending' => $query->where('status', 'pending'),
                            'cancelled' => $query->where('status', 'cancelled'),
                            default => $query,
                        };
                    }),
                Filter::make('upcoming')
                    ->label('Check-in viitor')
                    ->query(fn (Builder $query): Builder => $query->whereDate('check_in', '>=', now()->toDateString())),
            ])
            ->recordUrl(fn (Booking $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }

    public static function statusLabel(Booking $booking): string
    {
        return match ($booking->status) {
            'confirmed' => ($booking->check_out?->toDateString() ?? '') < now()->toDateString()
                ? 'Finalizată'
                : 'Activă',
            'pending' => 'În așteptare',
            'cancelled' => 'Anulată',
            default => (string) $booking->status,
        };
    }

    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ').' MDL';
    }
}
