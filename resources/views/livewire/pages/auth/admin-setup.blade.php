<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $userId = '';

    #[Locked]
    public string $name = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function activate(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::findOrFail($this->userId);

        if ($user->hasVerifiedEmail()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $user->forceFill([
            'password' => $this->password,
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);

        $this->redirect('/admin', navigate: false);
    }
}; ?>

<div>
    <p class="font-display text-xl uppercase">Activate your admin account</p>
    <p class="mt-2 text-sm text-ink-500">Welcome, {{ $name }}. Set a password to get started.</p>

    <form wire:submit="activate" class="mt-6 space-y-5">
        <div>
            <x-input-label value="Email" />
            <p class="mt-1 text-sm text-ink-600">{{ $email }}</p>
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input wire:model="password" id="password" class="mt-1" type="password" name="password" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm password" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full py-3">
            Activate account
        </x-primary-button>
    </form>
</div>
