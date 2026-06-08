<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Navigation\AdminNavigationGroup;
use App\Legacy\LegacyBridge;
use App\Services\PropertyCreateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class AddProperty extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlusCircle;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Proprietati;

    protected static ?string $navigationLabel = 'Adaugă proprietate';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'properties/create';

    protected static ?string $title = 'Adaugă proprietate';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'city' => 'Chișinău',
            'property_type' => 'Apartament',
            'min_stay' => 1,
            'check_in_start' => '14:00',
            'check_in_end' => '21:00',
            'check_out_start' => '08:00',
            'check_out_end' => '11:00',
            'amenities' => [],
            'pricing_periods' => [],
            'stay_discounts_global' => [],
            'new_images' => [],
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Adaugă locuință nouă';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Completează datele ca în formularul vechi de administrare.';
    }

    public function form(Schema $schema): Schema
    {
        LegacyBridge::boot();

        $amenityOptions = [];
        foreach (lh_property_amenity_categories() as $items) {
            foreach ($items as $key => $info) {
                $amenityOptions[(string) $key] = (string) ($info[0] ?? $key);
            }
        }

        $currency = lh_currency_code();

        return $schema
            ->components([
                Tabs::make('PropertyTabs')
                    ->tabs([
                        Tab::make('general')
                            ->label('General')
                            ->schema([
                                Section::make('Identitate & locație')
                                    ->schema([
                                        TextInput::make('title')->label('Titlu public')->required(),
                                        TextInput::make('lot_id')->label('LOT ID')->required(),
                                        TextInput::make('city')->label('Oraș')->default('Chișinău'),
                                        TextInput::make('district')->label('Sector'),
                                        TextInput::make('address')->label('Adresă'),
                                    ])
                                    ->columns(2),
                                Section::make('Detalii tehnice')
                                    ->schema([
                                        Select::make('property_type')
                                            ->label('Tip')
                                            ->options([
                                                'Apartament' => 'Apartament',
                                                'Studio' => 'Studio',
                                                'Casă' => 'Casă',
                                            ]),
                                        TextInput::make('rooms')->label('Camere')->numeric(),
                                        TextInput::make('sleep_capacity')->label('Capacitate')->numeric(),
                                        TextInput::make('area_sqm')->label('Mp')->numeric(),
                                        TextInput::make('floor')->label('Etaj')->numeric(),
                                        TextInput::make('min_stay')->label('Min. nopți')->numeric()->default(1),
                                    ])
                                    ->columns(3),
                                Section::make('Preț de bază')
                                    ->schema([
                                        TextInput::make('price')->label("Preț standard ({$currency} / noapte)")->numeric()->required(),
                                        TextInput::make('price_weekend')->label("Preț weekend ({$currency} / noapte)")->numeric(),
                                        TextInput::make('guests_included')->label('Oaspeți incluși')->numeric(),
                                        TextInput::make('extra_guest_price')->label("Supliment oaspete ({$currency})")->numeric(),
                                    ])
                                    ->columns(2),
                                Section::make('Check-in & Check-out')
                                    ->schema([
                                        TimePicker::make('check_in_start')->label('Check-in de la')->seconds(false),
                                        TimePicker::make('check_in_end')->label('Check-in până la')->seconds(false),
                                        TimePicker::make('check_out_start')->label('Check-out de la')->seconds(false),
                                        TimePicker::make('check_out_end')->label('Check-out până la')->seconds(false),
                                    ])
                                    ->columns(2),
                                Section::make('Descriere')
                                    ->schema([
                                        Textarea::make('description_long')->label('Descriere marketing')->rows(6),
                                        Textarea::make('pre_checkin_email_message')->label('Email reminder înainte de check-in')->rows(5),
                                    ]),
                                Section::make('Facilități')
                                    ->schema([
                                        CheckboxList::make('amenities')
                                            ->label('Dotări')
                                            ->options($amenityOptions)
                                            ->columns(3)
                                            ->bulkToggleable(),
                                    ]),
                            ]),
                        Tab::make('translations')
                            ->label('Traduceri EN / RU')
                            ->schema([
                                Section::make('English')
                                    ->schema([
                                        TextInput::make('tr_en_title')->label('Titlu (EN)'),
                                        TextInput::make('tr_en_slug')->label('Slug URL (EN)'),
                                        Textarea::make('tr_en_description_long')->label('Descriere (EN)')->rows(5),
                                    ]),
                                Section::make('Русский')
                                    ->schema([
                                        TextInput::make('tr_ru_title')->label('Titlu (RU)'),
                                        TextInput::make('tr_ru_slug')->label('Slug URL (RU)'),
                                        Textarea::make('tr_ru_description_long')->label('Descriere (RU)')->rows(5),
                                    ]),
                            ]),
                        Tab::make('pricing')
                            ->label('Prețuri & reduceri')
                            ->schema([
                                Repeater::make('pricing_periods')
                                    ->label('Perioade cu preț special')
                                    ->schema([
                                        TextInput::make('label')->label('Denumire'),
                                        DatePicker::make('date_start')->label('De la'),
                                        DatePicker::make('date_end')->label('Până la'),
                                        TextInput::make('price')->label("Preț ({$currency})")->numeric(),
                                        TextInput::make('price_weekend')->label("Weekend ({$currency})")->numeric(),
                                        TextInput::make('min_stay')->label('Min. nopți')->numeric(),
                                        Repeater::make('stay_discounts')
                                            ->label('Reduceri perioadă')
                                            ->schema([
                                                TextInput::make('min_nights')->label('Min. nopți')->numeric(),
                                                TextInput::make('value')->label('Reducere')->numeric(),
                                                Select::make('unit')
                                                    ->label('Unitate')
                                                    ->options([
                                                        'percent' => '%',
                                                        'fixed_stay' => "{$currency} tot sejurul",
                                                    ])
                                                    ->default('percent'),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->collapsible(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->collapsible(),
                                Repeater::make('stay_discounts_global')
                                    ->label('Reduceri după durata sejurului (global)')
                                    ->schema([
                                        TextInput::make('min_nights')->label('La peste (nopți)')->numeric(),
                                        TextInput::make('value')->label('Reducere')->numeric(),
                                        Select::make('unit')
                                            ->label('Unitate')
                                            ->options([
                                                'percent' => '%',
                                                'fixed_stay' => "{$currency} tot sejurul",
                                            ])
                                            ->default('percent'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->collapsible(),
                            ]),
                        Tab::make('ical')
                            ->label('iCal')
                            ->schema([
                                TextInput::make('ical_import_link')->label('Link iCal import (sincronizare)')->url(),
                            ]),
                        Tab::make('gallery')
                            ->label('Galerie')
                            ->schema([
                                FileUpload::make('new_images')
                                    ->label('Imagini (trage pentru a reordona)')
                                    ->disk('public')
                                    ->directory('uploads/properties/incoming')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(30)
                                    ->helperText('Ordinea din listă = ordinea din galerie. Prima imagine devine coperta.')
                                    ->dehydrated(true),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();
            $this->form->validate();
            $data = $this->form->getState();

            $newImages = $data['new_images'] ?? [];
            unset($data['new_images']);

            $result = app(PropertyCreateService::class)->create(
                $data,
                is_array($newImages) ? $newImages : [],
            );

            if (! ($result['ok'] ?? false)) {
                Notification::make()
                    ->title('Eroare la creare')
                    ->body((string) ($result['error'] ?? 'Crearea a eșuat.'))
                    ->danger()
                    ->send();

                $this->rollBackDatabaseTransaction();

                return;
            }

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title('Proprietate creată')
                ->success()
                ->send();

            $this->redirect(EditProperty::getUrl(['record' => (int) $result['property_id']]));
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();
            throw $exception;
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Creează proprietatea')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                    Action::make('back')
                        ->label('Înapoi la proprietăți')
                        ->url(Dashboard::getUrl())
                        ->color('gray'),
                ]),
            ]);
    }

}
