<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PropertyStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $total = Property::query()->count();
        $active = Property::query()->where('is_active', true)->count();
        $inactive = Property::query()->where('is_active', false)->count();

        return [
            Stat::make('Toate', (string) $total)
                ->description('Proprietăți în sistem')
                ->color('gray'),
            Stat::make('Active', (string) $active)
                ->description('Listate pe site')
                ->color('success'),
            Stat::make('Inactive', (string) $inactive)
                ->description('Ascunse temporar')
                ->color('warning'),
        ];
    }
}
