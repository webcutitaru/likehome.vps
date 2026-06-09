<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Filament\Resources\BookingResource;
use App\Services\AdminCalendarService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class PropertyCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Rezervari;

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Calendar rezervări și prețuri';

    /** @var array<string, mixed> */
    protected array $calendarViewData = [];

    public function mount(AdminCalendarService $calendarService): void
    {
        $this->calendarViewData = $calendarService->buildViewData(request());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.pages.property-calendar-content')
                    ->viewData(fn (): array => $this->resolveCalendarViewData()),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCalendarViewData(): array
    {
        if ($this->calendarViewData === []) {
            $this->calendarViewData = app(AdminCalendarService::class)->buildViewData(request());
        }

        return array_merge($this->calendarViewData, [
            'calendarPageUrl' => static::getUrl(),
            'calendarActionUrl' => route('admin.calendar.action'),
            'bookingActionUrl' => route('admin.booking.action'),
            'bookingsListUrl' => BookingResource::getUrl('index'),
            'editPropertySampleUrl' => EditProperty::getUrl(['record' => 1]),
        ]);
    }

    public function getHeading(): string|Htmlable|null
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
