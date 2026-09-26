<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The admin side of the bank transfer fallback: the account name has to be
 * confirmed by Paystack before the option can go live for customers.
 */
class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function fakePaystack(string $accountName = 'DORA CREATIONS LTD'): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => ['account_name' => $accountName],
            ], 200),
            'api.paystack.co/bank*' => Http::response([
                'status' => true,
                'data' => [['code' => '058', 'name' => 'GTBank']],
            ], 200),
        ]);
    }

    public function test_an_admin_can_open_the_settings_page(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageSiteSettings::class)
            ->assertOk()
            ->assertSee('Bank transfer');
    }

    public function test_verifying_an_account_stores_the_paystack_account_name(): void
    {
        $this->fakePaystack();

        $component = Livewire::actingAs($this->admin())->test(ManageSiteSettings::class);

        $component->set('data.bank_transfer_enabled', true);
        $component->set('data.bank_code', '058');
        $component->set('data.bank_account_number', '0123456789');

        $component->callFormComponentAction('verifyBankAccountAction', 'verifyBankAccount');

        $this->assertSame('DORA CREATIONS LTD', $component->get('data.bank_account_name'));
    }

    public function test_an_unverifiable_account_is_not_stored(): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response(['status' => false], 422),
            'api.paystack.co/bank*' => Http::response(['status' => true, 'data' => []], 200),
        ]);

        $component = Livewire::actingAs($this->admin())->test(ManageSiteSettings::class);

        $component->set('data.bank_transfer_enabled', true);
        $component->set('data.bank_code', '058');
        $component->set('data.bank_account_number', '0000000000');

        $component->callFormComponentAction('verifyBankAccountAction', 'verifyBankAccount');

        $this->assertNull($component->get('data.bank_account_name'));
    }

    public function test_changing_the_account_number_clears_a_previous_verification(): void
    {
        $this->fakePaystack();

        $component = Livewire::actingAs($this->admin())->test(ManageSiteSettings::class);

        $component->set('data.bank_transfer_enabled', true);
        $component->set('data.bank_code', '058');
        $component->set('data.bank_account_number', '0123456789');
        $component->callFormComponentAction('verifyBankAccountAction', 'verifyBankAccount');

        $this->assertSame('DORA CREATIONS LTD', $component->get('data.bank_account_name'));

        // Pointing at a different account must not keep the old verified name.
        $component->set('data.bank_account_number', '9999999999');

        $this->assertNull($component->get('data.bank_account_name'));
    }

    public function test_bank_transfer_cannot_be_switched_on_without_a_verified_account(): void
    {
        $component = Livewire::actingAs($this->admin())->test(ManageSiteSettings::class);

        $component->set('data.bank_transfer_enabled', true);
        $component->set('data.bank_account_name', null);
        $component->call('save');

        $this->assertFalse(SiteSetting::current()->bank_transfer_enabled);
    }

    public function test_a_verified_account_can_be_saved_and_is_offered(): void
    {
        $this->fakePaystack();

        $component = Livewire::actingAs($this->admin())->test(ManageSiteSettings::class);

        $component->set('data.bank_transfer_enabled', true);
        $component->set('data.bank_code', '058');
        $component->set('data.bank_account_number', '0123456789');
        $component->callFormComponentAction('verifyBankAccountAction', 'verifyBankAccount');
        $component->call('save');

        $settings = SiteSetting::current();

        $this->assertTrue($settings->bank_transfer_enabled);
        $this->assertSame('0123456789', $settings->bank_account_number);
        $this->assertSame('DORA CREATIONS LTD', $settings->bank_account_name);
        $this->assertSame('GTBank', $settings->bank_name, 'The bank display name should follow the chosen code');
        $this->assertTrue($settings->bankTransferIsAvailable());
    }
}
