<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="font-display text-xl uppercase">Confirm your password</p>
    <p class="mt-2 text-sm text-ink-500">
        {{ __('This is a secure area. Please confirm your password before continuing.') }}
    </p>

    <form wire:submit="confirmPassword" class="mt-6 space-y-5">
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password"
                          id="password"
                          class="mt-1"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</div>
