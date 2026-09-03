<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $phone = '';

    public $avatar = null;

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
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($validated['avatar']);

        if ($this->avatar) {
            $validated['avatar_path'] = $this->avatar->store('avatars', config('filesystems.image_disk'));
        }

        $user->fill($validated)->save();

        $this->avatar = null;
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
            <x-input-label value="Avatar" />
            <div class="mt-2 flex items-center gap-4">
                <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-full bg-ink-100">
                    @if ($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar preview" class="h-full w-full object-cover">
                    @elseif (auth()->user()->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-ink-400">
                            <x-heroicon-o-user class="h-8 w-8" />
                        </div>
                    @endif
                </div>
                <label class="cursor-pointer text-sm font-semibold text-brand-600 hover:underline">
                    Change photo
                    <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                </label>
            </div>
            <div wire:loading wire:target="avatar" class="mt-1 text-xs text-ink-400">Uploading…</div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

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
