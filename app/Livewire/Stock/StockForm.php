<?php

namespace App\Livewire\Stock;

use App\Exceptions\StockException;
use App\Livewire\Concerns\PicksVariant;
use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared plumbing for the receive, sell, adjust and transfer screens.
 */
#[Layout('layouts.app')]
abstract class StockForm extends Component
{
    use PicksVariant, WithShop;

    public ?int $locationId = null;

    public string $note = '';

    public function mount(): void
    {
        $this->locationId = $this->shop()->defaultLocation()?->id;
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function recent(): Collection
    {
        return StockMovement::query()
            ->with('variant.product', 'location')
            ->whereIn('type', $this->recentTypes())
            ->where('user_id', $this->user()->id)
            ->latestFirst()
            ->limit(5)
            ->get();
    }

    /**
     * @return list<string>
     */
    abstract protected function recentTypes(): array;

    protected function location(?int $id = null): Location
    {
        return Location::query()->findOrFail($id ?? $this->locationId);
    }

    protected function variant(): ProductVariant
    {
        return ProductVariant::query()->with('product')->findOrFail($this->variantId);
    }

    /**
     * Runs a stock change and turns rule violations into a form error.
     */
    protected function attempt(callable $callback, string $errorField = 'quantity'): bool
    {
        try {
            $callback();

            return true;
        } catch (StockException $e) {
            $this->addError($errorField, $e->getMessage());

            return false;
        }
    }

    protected function nullableNote(): ?string
    {
        $note = trim($this->note);

        return $note === '' ? null : $note;
    }
}
