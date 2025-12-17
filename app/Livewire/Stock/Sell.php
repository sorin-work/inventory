<?php

namespace App\Livewire\Stock;

use App\Models\ProductVariant;
use App\Services\StockService;
use App\Support\Money;
use Livewire\Attributes\Title;

#[Title('Record a sale')]
class Sell extends StockForm
{
    public string $quantity = '1';

    public string $unitPrice = '';

    protected function variantSelected(ProductVariant $variant): void
    {
        $this->unitPrice = number_format($variant->price_cents / 100, 2, '.', '');
    }

    public function save(StockService $stock): void
    {
        $this->validate([
            'variantId' => ['required', 'integer'],
            'locationId' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unitPrice' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'variantId.required' => 'Pick a product.',
        ]);

        $variant = $this->variant();
        $location = $this->location();

        $done = $this->attempt(fn () => $stock->sell(
            $variant,
            $location,
            (int) $this->quantity,
            $this->unitPrice === '' ? null : Money::parse($this->unitPrice),
            $this->user(),
            $this->nullableNote(),
        ));

        if ($done) {
            $this->notify("Sold {$this->quantity} × {$variant->sku} from {$location->name}.");
            $this->reset('quantity', 'note', 'variantId', 'unitPrice');
        }
    }

    public function render()
    {
        return view('livewire.stock.sell');
    }
}
