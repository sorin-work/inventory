<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\WithShop;
use App\Models\Supplier;
use App\Rules\UniqueInShop;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithShop;

    public ?Supplier $supplier = null;

    public string $name = '';

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $lead_time_days = '';

    public string $notes = '';

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier?->exists) {
            $this->supplier = $supplier;

            foreach (['name', 'contact_name', 'email', 'phone', 'address', 'lead_time_days', 'notes'] as $field) {
                $this->{$field} = (string) $supplier->{$field};
            }
        } else {
            $this->supplier = null;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new UniqueInShop('suppliers', 'name', $this->supplier?->id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['lead_time_days' => 'lead time']);

        $validated = array_map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value, $validated);

        $supplier = $this->supplier ?? new Supplier;
        $supplier->fill($validated)->save();

        session()->flash('status', $this->supplier ? 'Supplier saved.' : "{$supplier->name} added.");
        $this->redirectRoute('suppliers.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.suppliers.form')
            ->title($this->supplier ? 'Edit '.$this->supplier->name : 'New supplier');
    }
}
