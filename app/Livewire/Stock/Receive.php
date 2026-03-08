<?php

namespace App\Livewire\Stock;

use App\Enums\MovementType;
use App\Models\ProductVariant;
use App\Services\StockService;
use App\Support\Money;
use Livewire\Attributes\Title;

#[Title('Receive stock')]
class Receive extends StockForm
{
    public string $quantity = '1';

    public string $unitCost = '';

    protected function variantSelected(ProductVariant $variant): void
    {
        $this->unitCost = $variant->average_cost > 0 ? Money::toDecimal($variant->average_cost) : '';
    }

    public function save(StockService $stock): void
    {
        $this->validate([
            'variantId' => ['required', 'integer'],
            'locationId' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unitCost' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'variantId.required' => 'Pick a product.',
        ]);

        $variant = $this->variant();
        $location = $this->location();

        $done = $this->attempt(fn () => $stock->receive(
            $variant,
            $location,
            (int) $this->quantity,
            Money::parse($this->unitCost),
            $this->user(),
            $this->nullableNote(),
        ));

        if ($done) {
            $this->notify("Received {$this->quantity} × {$variant->sku} into {$location->name}.");
            $this->reset('quantity', 'note', 'variantId', 'unitCost');
        }
    }

    protected function recentTypes(): array
    {
        return [MovementType::Receipt->value];
    }

    public function render()
    {
        return view('livewire.stock.receive');
    }
}
