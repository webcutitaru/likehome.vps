<?php

namespace App\Filament\Resources;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Proprietati;

    protected static ?string $navigationLabel = 'Proprietăți';

    protected static ?string $modelLabel = 'proprietate';

    protected static ?string $pluralModelLabel = 'proprietăți';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informații de bază')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titlu')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('lot_id')
                            ->label('LOT ID')
                            ->required()
                            ->maxLength(64),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('city')
                            ->label('Oraș')
                            ->required()
                            ->maxLength(128),
                        TextInput::make('district')
                            ->label('Sector')
                            ->maxLength(128),
                        TextInput::make('address')
                            ->label('Adresă')
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Preț / noapte')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('MDL'),
                        Toggle::make('is_active')
                            ->label('Activă')
                            ->default(true),
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titlu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lot_id')
                    ->label('LOT')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label('Oraș')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Preț')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', ' ').' MDL')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activă')
                    ->boolean(),
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
                EditAction::make()
                    ->label('Editează')
                    ->url(fn (Property $record): string => EditProperty::getUrl(['record' => $record->id])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
