<?php

namespace App\Filament\Resources;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AdminActivityLogService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Administrare;

    protected static ?string $navigationLabel = 'Utilizatori';

    protected static ?string $modelLabel = 'utilizator';

    protected static ?string $pluralModelLabel = 'utilizatori';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cont')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nume')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->maxLength(64),
                        Select::make('role')
                            ->label('Rol')
                            ->options([
                                'manager' => 'Manager',
                                'admin' => 'Administrator',
                            ])
                            ->default('manager')
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Activ',
                                'disabled' => 'Dezactivat',
                            ])
                            ->default('active')
                            ->required(),
                        TextInput::make('password')
                            ->label('Parolă')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirmă parola')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
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
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nume')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'admin' ? 'Administrator' : 'Manager')
                    ->color(fn (string $state): string => $state === 'admin' ? 'gray' : 'info'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Activ' : 'Dezactivat')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('last_login_at')
                    ->label('Ultima autentificare')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Activ',
                        'disabled' => 'Dezactivat',
                    ]),
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Administrator',
                        'manager' => 'Manager',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('toggle_status')
                    ->label(fn (User $record): string => $record->status === 'active' ? 'Dezactivează' : 'Activează')
                    ->color(fn (User $record): string => $record->status === 'active' ? 'danger' : 'success')
                    ->visible(fn (User $record): bool => $record->role !== 'admin' && (int) $record->id !== (int) auth()->id())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $old = (string) $record->status;
                        $new = $old === 'active' ? 'disabled' : 'active';
                        $record->update(['status' => $new]);
                        app(AdminActivityLogService::class)->log(
                            'user_toggle_status',
                            'user',
                            (int) $record->id,
                            ['from' => $old, 'to' => $new],
                            auth()->id(),
                        );
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
