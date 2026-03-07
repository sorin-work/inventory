<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\WithShop;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Shop settings')]
class ShopSettings extends Component
{
    use WithShop;

    public string $name = '';

    public string $currency = 'USD';

    public function mount(): void
    {
        $this->authorizeOwner();

        $this->name = $this->shop()->name;
        $this->currency = $this->shop()->currency;
    }

    public function save(): void
    {
        $this->authorizeOwner();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', Rule::in(array_keys(Money::currencies()))],
        ]);

        $this->shop()->update($validated);

        session()->flash('status', 'Shop settings saved.');
        $this->redirectRoute('settings.shop', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.shop-settings');
    }
}
