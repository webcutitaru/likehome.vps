<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\EditProperty;
use App\Legacy\LegacyBridge;
use App\Models\Property;
use App\Services\AdminActivityLogService;
use App\Services\PropertyDeleteService;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PropertiesTableWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Proprietăți';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Property::query()->orderByDesc('id'))
            ->searchable()
            ->searchPlaceholder('Caută după titlu, LOT, oraș, adresă…')
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->getStateUsing(function (Property $record): string {
                        LegacyBridge::boot();
                        $images = array_filter(array_map('trim', explode(',', (string) $record->image_name)));
                        $first = $images[0] ?? 'default.jpg';

                        return lh_property_image_url((int) $record->id, $first, 'thumb');
                    })
                    ->imageSize(56)
                    ->square(),
                TextColumn::make('title')
                    ->label('Proprietate')
                    ->description(fn (Property $record): string => 'LOT: #'.$record->lot_id)
                    ->searchable(['title', 'lot_id', 'slug', 'city', 'address', 'district', 'location'])
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Locație')
                    ->description(fn (Property $record): string => (string) $record->address)
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Preț')
                    ->alignCenter()
                    ->formatStateUsing(function ($state): string {
                        LegacyBridge::boot();

                        return lh_format_money((float) $state, 0);
                    }),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Editare')
                    ->url(fn (Property $record): string => EditProperty::getUrl(['record' => $record->id])),
                Action::make('toggle')
                    ->label(fn (Property $record): string => $record->is_active ? 'Dezactivează' : 'Activează')
                    ->color(fn (Property $record): string => $record->is_active ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Property $record): void {
                        $old = $record->is_active ? 1 : 0;
                        $new = $old ? 0 : 1;
                        $record->update(['is_active' => (bool) $new]);
                        app(AdminActivityLogService::class)->log(
                            'property_toggle_active',
                            'property',
                            (int) $record->id,
                            ['from_active' => $old, 'to_active' => $new],
                            auth()->id(),
                        );
                    }),
                Action::make('delete')
                    ->label('Șterge')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->role === 'admin')
                    ->requiresConfirmation()
                    ->modalHeading('Șterge proprietatea')
                    ->action(function (Property $record, PropertyDeleteService $deleteService): void {
                        $result = $deleteService->delete($record, auth()->id());

                        Notification::make()
                            ->title($result['ok'] ? 'Proprietate ștearsă' : 'Ștergerea a eșuat')
                            ->body($result['ok'] ? null : (string) ($result['error'] ?? ''))
                            ->{$result['ok'] ? 'success' : 'danger'}()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
