<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <p class="font-display text-xl uppercase">Verify your email</p>
    <p class="mt-2 text-sm text-ink-500">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking the link we just emailed you? If you didn\'t receive it, we can send another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 rounded-lg bg-forest-50 px-4 py-3 text-sm font-medium text-forest-700">
            {{ __('A new verification link has been sent to the email address you provided.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend verification email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="text-sm font-semibold text-ink-500 underline hover:text-ink-900">
            {{ __('Log out') }}
        </button>
    </div>
</div>
