<?php

namespace App\Filament\Resources;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Resources\AdminActivityLogResource\Pages;
use App\Models\AdminActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AdminActivityLogResource extends Resource
{
    protected static ?string $model = AdminActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Administrare;

    protected static ?string $navigationLabel = 'Jurnal activitate';

    protected static ?string $modelLabel = 'înregistrare';

    protected static ?string $pluralModelLabel = 'jurnal activitate';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema;
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
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Utilizator')
                    ->description(fn (AdminActivityLog $record): string => (string) ($record->user?->email ?? ''))
                    ->placeholder('—'),
                TextColumn::make('action')
                    ->label('Acțiune')
                    ->formatStateUsing(fn (string $state): string => self::actionLabel($state))
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Entitate')
                    ->formatStateUsing(fn (?string $state, AdminActivityLog $record): string => trim(($state ?? '').' #'.($record->entity_id ?? '')))
                    ->placeholder('—'),
                TextColumn::make('details')
                    ->label('Detalii')
                    ->limit(80)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Utilizator')
                    ->options(fn (): array => User::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => $user->name.' · '.$user->email,
                        ])
                        ->all()),
                SelectFilter::make('action')
                    ->label('Acțiune')
                    ->options(fn (): array => AdminActivityLog::query()
                        ->select('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action')
                        ->mapWithKeys(fn (string $action): array => [$action => self::actionLabel($action)])
                        ->all()),
                Filter::make('date_range')
                    ->label('Perioadă')
                    ->schema([
                        DatePicker::make('from')
                            ->label('De la')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Până la')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'login_success' => 'Autentificare reușită',
            'login_failed' => 'Autentificare eșuată',
            'login_failed_disabled' => 'Autentificare refuzată (cont dezactivat)',
            'logout' => 'Deconectare',
            'property_create' => 'Proprietate nouă',
            'property_update' => 'Proprietate actualizată',
            'property_delete' => 'Proprietate ștearsă',
            'property_toggle_active' => 'Status activ/inactiv proprietate',
            'booking_confirm' => 'Rezervare confirmată',
            'booking_cancel' => 'Rezervare anulată',
            'booking_update' => 'Rezervare actualizată',
            'booking_checkin_reminder_manual' => 'Reminder check-in trimis',
            'booking_refund' => 'Rambursare rezervare',
            'calendar_pricing_special' => 'Preț special calendar',
            'manual_block_add' => 'Blocare manuală adăugată',
            'manual_block_delete' => 'Blocare manuală ștearsă',
            'user_create' => 'Utilizator creat',
            'user_update' => 'Utilizator actualizat',
            'user_toggle_status' => 'Status utilizator schimbat',
            'user_delete' => 'Utilizator șters',
            default => $action,
        };
    }
}
