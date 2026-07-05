<?php

namespace App\Livewire\Stock;

use App\Enums\MovementType;
use App\Services\StockService;
use Livewire\Attributes\Title;

#[Title('Transfer stock')]
class Transfer extends StockForm
{
    public ?int $toLocationId = null;

    public string $quantity = '1';

    public function mount(): void
    {
        parent::mount();

        $this->toLocationId = $this->locations()->firstWhere('id', '!=', $this->locationId)?->id;
    }

    public function swap(): void
    {
        [$this->locationId, $this->toLocationId] = [$this->toLocationId, $this->locationId];
    }

    public function save(StockService $stock): void
    {
        $this->validate([
            'variantId' => ['required', 'integer'],
            'locationId' => ['required', 'integer'],
            'toLocationId' => ['required', 'integer', 'different:locationId'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'variantId.required' => 'Pick a product.',
            'toLocationId.different' => 'Pick a different location to move the stock to.',
        ]);

        $variant = $this->variant();
        $from = $this->location();
        $to = $this->location($this->toLocationId);

        $done = $this->attempt(fn () => $stock->transfer(
            $variant,
            $from,
            $to,
            (int) $this->quantity,
            $this->user(),
            $this->nullableNote(),
        ));

        if ($done) {
            $this->notify("Moved {$this->quantity} × {$variant->sku} from {$from->name} to {$to->name}.");
            $this->reset('quantity', 'note', 'variantId');
        }
    }

    protected function recentTypes(): array
    {
        return [MovementType::TransferOut->value];
    }

    public function render()
    {
        return view('livewire.stock.transfer');
    }
}
