<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\ConfiguresPropertyGalleryUpload;
use App\Filament\Concerns\InteractsWithPropertyGallery;
use App\Filament\Pages\Dashboard;
use App\Legacy\LegacyBridge;
use App\Models\Property;
use App\Services\PropertyDeleteService;
use App\Services\PropertySaveService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * @property-read Schema $form
 */
class EditProperty extends Page
{
    use CanUseDatabaseTransactions;
    use ConfiguresPropertyGalleryUpload;
    use InteractsWithPropertyGallery;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'edit-property/{record}';

    protected static ?string $title = 'Editează proprietatea';

    public ?array $data = [];

    #[Locked]
    public Property|int|string|null $record = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveProperty($record);

        try {
            $this->fillForm();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Nu s-au putut încărca datele proprietății')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->data = [];
        }
    }

    public function hydrate(): void
    {
        if (is_int($this->record) || is_string($this->record)) {
            $this->record = $this->resolveProperty($this->record);
        }
    }

    protected function resolveProperty(int|string $key): Property
    {
        return Property::query()->findOrFail($key);
    }

    public function getProperty(): Property
    {
        abort_unless($this->record instanceof Property, 404);

        return $this->record;
    }

    protected function fillForm(): void
    {
        $service = app(PropertySaveService::class);
        $data = $service->loadFormData($this->getProperty());

        foreach (['check_in_start', 'check_in_end', 'check_out_start', 'check_out_end'] as $timeField) {
            $raw = trim((string) ($data[$timeField] ?? ''));
            if ($raw !== '' && preg_match('/^(\d{2}:\d{2})/', $raw, $m)) {
                $data[$timeField] = $m[1];
            }
        }

        $this->form->fill($data);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Editează: '.$this->getProperty()->lot_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Șterge proprietatea')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->role === 'admin')
                ->requiresConfirmation()
                ->modalHeading('Șterge proprietatea')
                ->modalDescription('Acțiunea este ireversibilă: se șterg imaginile și înregistrarea din baza de date.')
                ->action(function (PropertyDeleteService $deleteService): void {
                    $result = $deleteService->delete($this->getProperty(), auth()->id());

                    if (! ($result['ok'] ?? false)) {
                        Notification::make()
                            ->title('Ștergerea a eșuat')
                            ->body((string) ($result['error'] ?? 'Încearcă din nou.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Proprietatea a fost ștearsă')
                        ->success()
                        ->send();

                    $this->redirect(Dashboard::getUrl());
                }),
        ];
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
                                TextInput::make('ical_export_url')
                                    ->label('Link iCal export')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Tab::make('manual_blocks')
                            ->label('Blocări manuale')
                            ->schema([
                                Repeater::make('manual_blocks')
                                    ->label('Blocări active')
                                    ->schema([
                                        Hidden::make('id'),
                                        DatePicker::make('start_date')->label('De la')->disabled(),
                                        DatePicker::make('end_date')->label('Până la')->disabled(),
                                        TextInput::make('notes')->label('Notă')->disabled(),
                                    ])
                                    ->defaultItems(0)
                                    ->deletable(),
                                Repeater::make('manual_blocks_new')
                                    ->label('Adaugă blocare')
                                    ->schema([
                                        DatePicker::make('start_date')->label('De la')->required(),
                                        DatePicker::make('end_date')->label('Până la (ziua plecării)')->required(),
                                        TextInput::make('notes')->label('Notă'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->collapsible(),
                            ]),
                        Tab::make('gallery')
                            ->label('Galerie')
                            ->schema([
                                View::make('filament.pages.property-gallery-dnd')
                                    ->viewData(fn (): array => [
                                        'propertyId' => $this->getPropertyIdForUpload(),
                                        'images' => $this->data['existing_images'] ?? [],
                                    ]),
                                $this->makePropertyGalleryUpload()
                                    ->label('Încarcă imagini noi')
                                    ->disk('public')
                                    ->directory(fn (): string => 'uploads/properties/'.$this->getPropertyIdForUpload().'/incoming'),
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
            unset($data['new_images'], $data['ical_export_url']);

            $data['existing_images'] = $this->normalizeExistingImagesForSave($data['existing_images'] ?? []);

            $originalBlockIds = collect(app(PropertySaveService::class)->loadFormData($this->getProperty())['manual_blocks'] ?? [])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            $currentBlockIds = collect($data['manual_blocks'] ?? [])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            $data['manual_blocks_deleted'] = $originalBlockIds->diff($currentBlockIds)->values()->all();

            $service = app(PropertySaveService::class);
            $result = $service->save($this->getProperty(), $data, is_array($newImages) ? $newImages : []);

            if (! ($result['ok'] ?? false)) {
                Notification::make()
                    ->title('Eroare la salvare')
                    ->body((string) ($result['error'] ?? 'Salvarea a eșuat.'))
                    ->danger()
                    ->send();

                $this->rollBackDatabaseTransaction();

                return;
            }

            $this->commitDatabaseTransaction();
            $this->getProperty()->refresh();
            $this->fillForm();

            Notification::make()
                ->title('Proprietate actualizată')
                ->success()
                ->send();
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

    private function getPropertyIdForUpload(): int
    {
        $record = $this->record;

        if ($record instanceof Property) {
            return (int) $record->getKey();
        }

        return (int) $record;
    }

    public function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Actualizează proprietatea')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                    Action::make('back')
                        ->label('Înapoi la dashboard')
                        ->url(Dashboard::getUrl())
                        ->color('gray'),
                ]),
            ]);
    }

}
