<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $phone = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->phone = Auth::user()->phone ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user->fill($validated)->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-lg uppercase">Profile information</h2>
        <p class="mt-1 text-sm text-ink-500">Update your name and phone number.</p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 max-w-md space-y-6">
        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input wire:model="phone" id="phone" name="phone" type="tel" class="mt-1" required autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label value="Email" />
            <p class="mt-1 text-sm text-ink-600">{{ auth()->user()->email }}</p>
            <p class="mt-1 text-xs text-ink-400">Your email address can't be changed here — contact support if you need it updated.</p>

            @unless (auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-ink-700">
                        Your email address is unverified.
                        <button wire:click.prevent="sendVerification" class="font-semibold text-brand-600 hover:underline">
                            Resend verification email
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-forest-600">A new verification link has been sent.</p>
                    @endif
                </div>
            @endunless
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Save</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                Saved.
            </x-action-message>
        </div>
    </form>
</section>
