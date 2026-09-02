<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Suspend the currently authenticated user's account. Records are kept —
     * "deletion" only flips the account to suspended in the backend.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();
        $user->suspend();

        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="font-display text-lg uppercase">Delete account</h2>
        <p class="mt-1 text-sm text-ink-500">
            This deactivates your account — you'll be signed out and won't be able to log back in.
            Your order history is kept; contact us if you'd like it fully removed.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Delete account</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="font-display text-lg uppercase">Are you sure you want to delete your account?</h2>

            <p class="mt-2 text-sm text-ink-500">
                Your account will be deactivated immediately and you'll be signed out. Enter your password to confirm.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Password" class="sr-only" />
                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Password"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>

                <x-danger-button>
                    Delete account
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
