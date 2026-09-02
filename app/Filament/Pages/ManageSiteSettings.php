<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Site';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static string $view = 'filament.pages.manage-site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Maintenance')
                ->schema([
                    Forms\Components\Toggle::make('maintenance_mode')
                        ->label('Maintenance mode')
                        ->helperText('When on, visitors see a maintenance page instead of the storefront.'),
                ]),

            Forms\Components\Section::make('Site meta')
                ->schema([
                    Forms\Components\TextInput::make('site_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('meta_title')->maxLength(255),
                    Forms\Components\Textarea::make('meta_description')->rows(2)->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Contact & social')
                ->schema([
                    Forms\Components\TextInput::make('contact_email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('contact_phone')->tel()->maxLength(255),
                    Forms\Components\TextInput::make('social_instagram')->url()->maxLength(255),
                    Forms\Components\TextInput::make('social_twitter')->url()->maxLength(255),
                    Forms\Components\TextInput::make('social_facebook')->url()->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('Shipping')
                ->schema([
                    Forms\Components\TextInput::make('shipping_flat_rate_kobo')
                        ->label('Flat shipping rate (₦)')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : 0),
                    Forms\Components\TextInput::make('free_shipping_threshold_kobo')
                        ->label('Free shipping over (₦, optional)')
                        ->numeric()
                        ->prefix('₦')
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round($state * 100) : null),
                ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::current()->update($data);

        Notification::make()->title('Settings saved')->success()->send();
    }
}
