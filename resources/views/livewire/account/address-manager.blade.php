@php
    $nigerianStates = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];
@endphp

<div>
    <div class="space-y-4">
        @forelse ($addresses as $address)
            <div wire:key="address-{{ $address->id }}"
                class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between {{ $selectable && $selectedId === $address->id ? 'border-brand-500 bg-brand-50' : 'border-ink-100' }}">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold">{{ $address->label ?: 'Address' }}</p>
                        @if ($address->is_default)
                            <span class="rounded-full bg-forest-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-forest-700">Default</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-ink-600">{{ $address->full_name }} · {{ $address->phone }}</p>
                    <p class="text-sm text-ink-500">{{ $address->toSingleLine() }}</p>
                </div>

                <div class="flex flex-shrink-0 flex-wrap gap-3 text-xs font-semibold uppercase tracking-wide">
                    @if ($selectable)
                        <button wire:click="select({{ $address->id }})" class="text-brand-600 hover:underline">
                            {{ $selectedId === $address->id ? 'Selected ✓' : 'Use this address' }}
                        </button>
                    @endif
                    @unless ($address->is_default)
                        <button wire:click="makeDefault({{ $address->id }})" class="text-ink-500 hover:text-ink-900">Make default</button>
                    @endunless
                    <button wire:click="startEdit({{ $address->id }})" class="text-ink-500 hover:text-ink-900">Edit</button>
                    <button wire:click="delete({{ $address->id }})" wire:confirm="Remove this address?" class="text-red-500 hover:text-red-700">Remove</button>
                </div>
            </div>
        @empty
            <p class="text-sm text-ink-400">No saved addresses yet.</p>
        @endforelse
    </div>

    @if (! $showForm)
        <button wire:click="startCreate" type="button" class="mt-5 text-sm font-semibold uppercase tracking-wide text-brand-600 hover:underline">
            + Add a new address
        </button>
    @else
        <form wire:submit="save" class="mt-6 grid grid-cols-1 gap-4 rounded-xl border border-ink-100 bg-paper-soft p-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="label" value="Label (e.g. Home, Office)" />
                <x-text-input wire:model="label" id="label" class="mt-1" type="text" />
                <x-input-error :messages="$errors->get('label')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="full_name" value="Full name" />
                <x-text-input wire:model="full_name" id="full_name" class="mt-1" type="text" />
                <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input wire:model="phone" id="phone" class="mt-1" type="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="state" value="State" />
                <select wire:model="state" id="state" class="mt-1 w-full rounded-lg border-ink-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Select state</option>
                    @foreach ($nigerianStates as $stateOption)
                        <option value="{{ $stateOption }}">{{ $stateOption }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('state')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="city" value="City" />
                <x-text-input wire:model="city" id="city" class="mt-1" type="text" />
                <x-input-error :messages="$errors->get('city')" class="mt-1" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="line1" value="Street address" />
                <x-text-input wire:model="line1" id="line1" class="mt-1" type="text" />
                <x-input-error :messages="$errors->get('line1')" class="mt-1" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="line2" value="Apartment, suite, etc. (optional)" />
                <x-text-input wire:model="line2" id="line2" class="mt-1" type="text" />
            </div>

            <div>
                <x-input-label for="postal_code" value="Postal code (optional)" />
                <x-text-input wire:model="postal_code" id="postal_code" class="mt-1" type="text" />
            </div>

            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-ink-600">
                    <input wire:model="is_default" type="checkbox" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    Set as default address
                </label>
            </div>

            <div class="sm:col-span-2 flex gap-3">
                <x-primary-button type="submit">Save address</x-primary-button>
                <x-secondary-button type="button" wire:click="cancel">Cancel</x-secondary-button>
            </div>
        </form>
    @endif
</div>
