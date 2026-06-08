<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Widgets\PropertiesTableWidget;
use App\Filament\Widgets\PropertyStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Panou;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    public function getHeading(): string|Htmlable
    {
        $user = auth()->user();
        $name = $user?->name ?? 'Admin';

        return "Salutare, {$name}!";
    }

    public function getSubheading(): string|Htmlable|null
    {
        $user = auth()->user();
        $role = $user?->role ?? null;

        return $role
            ? "Gestionează rapid proprietățile active și inactive · rol: {$role}"
            : 'Gestionează rapid proprietățile active și inactive';
    }

    /**
     * @return array<class-string<WidgetConfiguration>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            PropertyStatsWidget::class,
            PropertiesTableWidget::class,
        ];
    }
}
