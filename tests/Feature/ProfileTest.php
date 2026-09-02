<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.settings'));

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('phone', '+2348011112222')
            ->call('updateProfileInformation');

        $component->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('+2348011112222', $user->phone);
    }

    public function test_email_cannot_be_changed_via_profile_form(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        $this->actingAs($user);

        Volt::test('profile.update-profile-information-form')
            ->set('name', 'Updated Name')
            ->set('phone', '+2348011112222')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('original@example.com', $user->fresh()->email);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        $user->refresh();
        $this->assertNotNull($user);
        $this->assertTrue($user->isSuspended());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertFalse($user->fresh()->isSuspended());
    }
}
