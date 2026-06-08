<?php

namespace App\Filament\Resources;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Resources\DiscountCouponResource\Pages;
use App\Models\DiscountCoupon;
use App\Models\Property;
use App\Services\DiscountCouponService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DiscountCouponResource extends Resource
{
    protected static ?string $model = DiscountCoupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Reduceri;

    protected static ?string $navigationLabel = 'Cupoane';

    protected static ?string $modelLabel = 'cupon';

    protected static ?string $pluralModelLabel = 'cupoane';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalii cupon')
                    ->schema([
                        TextInput::make('code')
                            ->label('Cod (unic)')
                            ->required()
                            ->maxLength(64)
                            ->dehydrateStateUsing(fn (?string $state): string => app(DiscountCouponService::class)->normalizeCode((string) $state))
                            ->extraInputAttributes(['class' => 'uppercase']),
                        Select::make('discount_type')
                            ->label('Tip reducere')
                            ->options([
                                'percent' => 'Procent',
                                'fixed' => 'Sumă fixă',
                            ])
                            ->default('percent')
                            ->required()
                            ->live(),
                        TextInput::make('discount_value')
                            ->label('Valoare')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01),
                        DatePicker::make('valid_from')
                            ->label('Valid de la')
                            ->native(false),
                        DatePicker::make('valid_to')
                            ->label('Valid până la')
                            ->native(false),
                        TextInput::make('max_redemptions')
                            ->label('Max. utilizări')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Gol = nelimitat'),
                        Toggle::make('applies_all_properties')
                            ->label('Toate proprietățile')
                            ->default(true)
                            ->live(),
                        Toggle::make('is_active')
                            ->label('Activ')
                            ->default(true),
                        CheckboxList::make('property_ids')
                            ->label('Proprietăți')
                            ->options(fn (): array => Property::query()
                                ->orderBy('title')
                                ->get()
                                ->mapWithKeys(fn (Property $property): array => [
                                    $property->id => $property->title.' · LOT '.$property->lot_id,
                                ])
                                ->all())
                            ->columns(2)
                            ->visible(fn (Get $get): bool => ! (bool) $get('applies_all_properties'))
                            ->dehydrated(fn (Get $get): bool => ! (bool) $get('applies_all_properties')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Cod')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_type')
                    ->label('Reducere')
                    ->formatStateUsing(fn (DiscountCoupon $record): string => $record->discount_type === 'percent'
                        ? $record->discount_value.'%'
                        : number_format((float) $record->discount_value, 0, ',', ' ').' MDL'),
                TextColumn::make('validity')
                    ->label('Valabilitate')
                    ->state(function (DiscountCoupon $record): string {
                        $from = $record->valid_from?->format('Y-m-d');
                        $to = $record->valid_to?->format('Y-m-d');

                        if (! $from && ! $to) {
                            return 'Nelimitată';
                        }

                        if ($from && $to) {
                            return $from.' → '.$to;
                        }

                        return $from ? 'De la '.$from : 'Până la '.$to;
                    }),
                TextColumn::make('confirmed_usages')
                    ->label('Utilizări')
                    ->state(fn (DiscountCoupon $record): int => $record->bookings()
                        ->where('status', 'confirmed')
                        ->count()),
                TextColumn::make('properties_scope')
                    ->label('Proprietăți')
                    ->state(function (DiscountCoupon $record): string {
                        if ($record->applies_all_properties) {
                            return 'Toate';
                        }

                        return $record->properties()
                            ->pluck('lot_id')
                            ->implode(', ') ?: '—';
                    }),
                IconColumn::make('is_active')
                    ->label('Activ')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('toggle_active')
                    ->label(fn (DiscountCoupon $record): string => $record->is_active ? 'Dezactivează' : 'Activează')
                    ->icon(fn (DiscountCoupon $record): string|BackedEnum => $record->is_active
                        ? Heroicon::OutlinedPauseCircle
                        : Heroicon::OutlinedPlayCircle)
                    ->action(function (DiscountCoupon $record, DiscountCouponService $service): void {
                        $service->toggleActive($record);

                        Notification::make()
                            ->title('Statusul cuponului a fost actualizat.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscountCoupons::route('/'),
            'create' => Pages\CreateDiscountCoupon::route('/create'),
            'edit' => Pages\EditDiscountCoupon::route('/{record}/edit'),
        ];
    }
}
