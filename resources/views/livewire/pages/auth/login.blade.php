<?php

use App\Livewire\Forms\LoginForm;
use App\Services\CartService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(CartService $cartService): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $cartService->mergeGuestCartIntoUser(auth()->user());

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <p class="font-display text-xl uppercase">Welcome back</p>
    <p class="mt-2 text-sm text-ink-500">Sign in to track your orders and check out faster.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-1" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="form.password" id="password" class="mt-1"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-ink-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-ink-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-brand-600 hover:underline" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full py-3">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        New here?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-600 hover:underline">Create an account</a>
    </p>
</div>
