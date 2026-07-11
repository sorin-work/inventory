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
        $remembered = session('stock.location');

        $this->locationId = $remembered && Location::query()->whereKey($remembered)->exists()
            ? (int) $remembered
            : $this->shop()->defaultLocation()?->id;
    }

    /**
     * The next stock screen opens at the location used last, which saves a
     * click when working through a delivery in the back room.
     */
    protected function rememberLocation(): void
    {
        session(['stock.location' => $this->locationId]);
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * Quantity on hand per location for the picked variant.
     *
     * @return array<int, int>
     */
    public function levels(): array
    {
        if ($this->variantId === null) {
            return [];
        }

        return ProductVariant::query()->findOrFail($this->variantId)
            ->stockLevels()
            ->pluck('quantity', 'location_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
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
            $this->rememberLocation();

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
