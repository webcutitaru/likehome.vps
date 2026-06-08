<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Pages\AddProperty;
use App\Filament\Resources\PropertyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Adaugă proprietate')
                ->url(AddProperty::getUrl()),
        ];
    }
}
