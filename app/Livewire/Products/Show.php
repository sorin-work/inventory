<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrderLine;
use App\Models\StockMovement;
use App\Support\AverageCost;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithPagination, WithShop;

    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function archive(): void
    {
        $this->authorizeOwner();

        $this->product->forceFill(['archived_at' => now()])->save();
        $this->notify('Product archived. It no longer shows up when picking stock.');
    }

    public function restore(): void
    {
        $this->authorizeOwner();

        $this->product->forceFill(['archived_at' => null])->save();
        $this->notify('Product restored.');
    }

    public function delete(): void
    {
        $this->authorizeOwner();

        if (! $this->canDelete()) {
            $this->dispatch('notify', message: 'This product has stock history. Archive it instead.');

            return;
        }

        $name = $this->product->name;
        $this->product->delete();

        session()->flash('status', "{$name} deleted.");
        $this->redirectRoute('products.index', navigate: true);
    }

    public function canDelete(): bool
    {
        $ids = $this->product->variants()->pluck('id');

        return ! StockMovement::query()->whereIn('product_variant_id', $ids)->exists()
            && ! PurchaseOrderLine::query()->whereIn('product_variant_id', $ids)->exists();
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * Quantity per variant per location.
     *
     * @return array<int, array<int, int>>
     */
    public function matrix(): array
    {
        $matrix = [];

        DB::table('stock_levels')
            ->whereIn('product_variant_id', $this->product->variants->pluck('id'))
            ->get(['product_variant_id', 'location_id', 'quantity'])
            ->each(function ($row) use (&$matrix) {
                $matrix[$row->product_variant_id][$row->location_id] = (int) $row->quantity;
            });

        return $matrix;
    }

    /**
     * Units still to arrive on open purchase orders, per variant.
     *
     * @return array<int, int>
     */
    public function onOrder(): array
    {
        return PurchaseOrderLine::query()
            ->whereIn('product_variant_id', $this->product->variants->pluck('id'))
            ->whereHas('purchaseOrder', fn ($q) => $q->open())
            ->selectRaw('product_variant_id, SUM(quantity_ordered - quantity_received) as outstanding')
            ->groupBy('product_variant_id')
            ->pluck('outstanding', 'product_variant_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    public function movements(): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['variant', 'location', 'user', 'purchaseOrder'])
            ->whereIn('product_variant_id', $this->product->variants->pluck('id'))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(20, pageName: 'ledger');
    }

    public function stockValue(): int
    {
        $matrix = $this->matrix();

        return $this->product->variants->sum(
            fn ($variant) => AverageCost::value(max(0, array_sum($matrix[$variant->id] ?? [])), $variant->average_cost)
        );
    }

    public function render()
    {
        $this->product->load(['variants', 'category', 'supplier']);

        return view('livewire.products.show')->title($this->product->name);
    }
}
