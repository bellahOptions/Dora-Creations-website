<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\Payments\PaystackBankResolver;
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

    protected function resolver(): PaystackBankResolver
    {
        return app(PaystackBankResolver::class);
    }

    /**
     * Paystack's bank list, plus whatever is already saved so an API outage
     * can never make the currently-configured bank disappear from the form.
     *
     * @return array<string, string>
     */
    public function bankOptions(): array
    {
        $banks = $this->resolver()->banks();
        $settings = SiteSetting::current();

        if ($settings->bank_code && ! isset($banks[$settings->bank_code])) {
            $banks[$settings->bank_code] = $settings->bank_name ?: $settings->bank_code;
        }

        return $banks;
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
                    Forms\Components\Textarea::make('meta_description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Aim for around 155-160 characters — search engines truncate longer descriptions.')
                        ->columnSpanFull(),
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

            Forms\Components\Section::make('Bank transfer')
                ->description('Let customers pay by direct bank transfer when they would rather not use a card. The account name is confirmed with Paystack before the option goes live, so customers are never shown a wrong account name.')
                ->schema([
                    Forms\Components\Toggle::make('bank_transfer_enabled')
                        ->label('Offer bank transfer at checkout')
                        ->live()
                        ->helperText('Customers only see this option once the account below is verified.')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('bank_code')
                        ->label('Bank')
                        ->options(fn (): array => $this->bankOptions())
                        ->getOptionLabelUsing(fn ($value): ?string => $this->bankOptions()[$value] ?? $value)
                        ->searchable()
                        ->live()
                        // Any change to the account invalidates a previously
                        // verified name — never let the two drift apart.
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('bank_account_name', null))
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('bank_transfer_enabled')),

                    Forms\Components\TextInput::make('bank_account_number')
                        ->label('Account number')
                        ->maxLength(20)
                        ->rules(['nullable', 'regex:/^\d{10}$/'])
                        ->validationMessages(['regex' => 'A Nigerian account number is 10 digits.'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('bank_account_name', null))
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('bank_transfer_enabled')),

                    // A disabled TextInput rather than a Placeholder: it needs to be
                    // a real state-bearing field, or the name Paystack resolves never
                    // makes it into the saved payload.
                    Forms\Components\TextInput::make('bank_account_name')
                        ->label('Account name')
                        ->disabled()
                        ->dehydrated()
                        ->placeholder('Not verified yet — enter the account details and choose "Verify account".')
                        ->helperText('Confirmed with Paystack when you verify the account.')
                        ->columnSpanFull(),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('verifyBankAccount')
                            ->label('Verify account')
                            ->icon('heroicon-o-check-badge')
                            ->action(function (Forms\Get $get, Forms\Set $set): void {
                                $number = (string) $get('bank_account_number');
                                $code = (string) $get('bank_code');

                                if ($number === '' || $code === '') {
                                    Notification::make()
                                        ->title('Choose a bank and enter the account number first')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $name = $this->resolver()->resolveAccountName($number, $code);

                                if (! $name) {
                                    $set('bank_account_name', null);

                                    Notification::make()
                                        ->title('Paystack could not verify that account')
                                        ->body('Double-check the bank and the 10-digit account number, then try again.')
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                $set('bank_account_name', $name);

                                Notification::make()
                                    ->title('Account verified')
                                    ->body("Account name: {$name}")
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull()->visible(fn (Forms\Get $get): bool => (bool) $get('bank_transfer_enabled')),

                    Forms\Components\Textarea::make('bank_transfer_note')
                        ->label('Extra instructions (optional)')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Shown to customers underneath the account details — e.g. how long transfers take to clear.')
                        ->columnSpanFull()
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('bank_transfer_enabled')),
                ])->columns(2),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (($data['bank_transfer_enabled'] ?? false) && blank($data['bank_account_name'] ?? null)) {
            Notification::make()
                ->title('Verify the bank account first')
                ->body('Bank transfer stays switched off until Paystack confirms the account name.')
                ->danger()
                ->send();

            return;
        }

        // Keep the display name in step with the code the admin picked.
        if (filled($data['bank_code'] ?? null)) {
            $data['bank_name'] = $this->bankOptions()[$data['bank_code']] ?? $data['bank_name'] ?? null;
        }

        SiteSetting::current()->update($data);

        Notification::make()->title('Settings saved')->success()->send();
    }
}
