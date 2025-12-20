<?php

namespace App\Livewire\Stock;

use App\Enums\AdjustmentReason;
use App\Services\StockService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;

#[Title('Adjust stock')]
class Adjust extends StockForm
{
    /** "change" adds or removes units, "count" sets what was counted. */
    public string $mode = 'change';

    public string $change = '';

    public string $counted = '';

    public string $reason = 'damaged';

    public function updatedMode(): void
    {
        $this->resetErrorBag();
    }

    public function save(StockService $stock): void
    {
        $rules = [
            'variantId' => ['required', 'integer'],
            'locationId' => ['required', 'integer'],
            'mode' => ['required', Rule::in(['change', 'count'])],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->mode === 'count') {
            $rules['counted'] = ['required', 'integer', 'min:0', 'max:1000000'];
        } else {
            $rules['change'] = ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'];
            $rules['reason'] = ['required', Rule::enum(AdjustmentReason::class)];
        }

        $this->validate($rules, [
            'variantId.required' => 'Pick a product.',
            'change.not_in' => 'Enter how many units to add (e.g. 3) or remove (e.g. -3).',
        ]);

        $reason = AdjustmentReason::from($this->mode === 'count' ? AdjustmentReason::StockCount->value : $this->reason);

        $variant = $this->variant();
        $location = $this->location();

        $done = $this->attempt(function () use ($stock, $variant, $location, $reason) {
            if ($this->mode === 'count') {
                $stock->recordCount($variant, $location, (int) $this->counted, $this->user(), $this->nullableNote());
            } else {
                $stock->adjust($variant, $location, (int) $this->change, $reason, $this->user(), $this->nullableNote());
            }
        }, $this->mode === 'count' ? 'counted' : 'change');

        if ($done) {
            $this->notify("Stock for {$variant->sku} at {$location->name} updated.");
            $this->reset('change', 'counted', 'note', 'variantId');
        }
    }

    public function reasons(): array
    {
        return collect(AdjustmentReason::options())->except(AdjustmentReason::StockCount->value)->all();
    }

    public function render()
    {
        return view('livewire.stock.adjust');
    }
}
