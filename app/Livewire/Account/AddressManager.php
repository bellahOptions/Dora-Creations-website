<?php

namespace App\Livewire\Account;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AddressManager extends Component
{
    /**
     * When true, each address shows a "Use this address" action that
     * dispatches its id instead of just managing the list (used at checkout).
     */
    public bool $selectable = false;

    public ?int $selectedId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $label = '';

    #[Validate('required|string|max:255')]
    public string $full_name = '';

    #[Validate('required|string|max:20')]
    public string $phone = '';

    #[Validate('required|string|max:255')]
    public string $state = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:255')]
    public string $line1 = '';

    #[Validate('nullable|string|max:255')]
    public string $line2 = '';

    #[Validate('nullable|string|max:20')]
    public string $postal_code = '';

    #[Validate('boolean')]
    public bool $is_default = false;

    public function mount(): void
    {
        $this->selectedId = Auth::user()->addresses()->where('is_default', true)->first()?->id;
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $addressId): void
    {
        $address = Auth::user()->addresses()->findOrFail($addressId);

        $this->editingId = $address->id;
        $this->label = $address->label ?? '';
        $this->full_name = $address->full_name;
        $this->phone = $address->phone;
        $this->state = $address->state;
        $this->city = $address->city;
        $this->line1 = $address->line1;
        $this->line2 = $address->line2 ?? '';
        $this->postal_code = $address->postal_code ?? '';
        $this->is_default = $address->is_default;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();

        if (! $this->editingId && Auth::user()->addresses()->doesntExist()) {
            $data['is_default'] = true;
        }

        if ($data['is_default']) {
            Auth::user()->addresses()->update(['is_default' => false]);
        }

        if ($this->editingId) {
            Auth::user()->addresses()->findOrFail($this->editingId)->update($data);
        } else {
            $address = Auth::user()->addresses()->create($data);
            $this->selectedId ??= $address->id;
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $addressId): void
    {
        Auth::user()->addresses()->findOrFail($addressId)->delete();
    }

    public function makeDefault(int $addressId): void
    {
        Auth::user()->addresses()->update(['is_default' => false]);
        Auth::user()->addresses()->findOrFail($addressId)->update(['is_default' => true]);
    }

    public function select(int $addressId): void
    {
        $this->selectedId = $addressId;
        $this->dispatch('address-selected', addressId: $addressId);
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'label', 'full_name', 'phone', 'state', 'city', 'line1', 'line2', 'postal_code', 'is_default']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.account.address-manager', [
            'addresses' => Auth::user()->addresses()->orderByDesc('is_default')->latest()->get(),
        ]);
    }
}
