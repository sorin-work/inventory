<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithShop;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Services\Reports\LowStockReport;
use App\Services\Reports\LowStockRow;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    use WithShop;

    /**
     * @return array{variants: int, units: int, sold_30d: int}
     */
    public function stats(): array
    {
        return [
            'variants' => ProductVariant::query()->whereHas('product', fn ($q) => $q->active())->count(),
            'units' => (int) ProductVariant::query()
                ->join('stock_levels', 'stock_levels.product_variant_id', '=', 'product_variants.id')
                ->where('stock_levels.quantity', '>', 0)
                ->sum('stock_levels.quantity'),
            'sold_30d' => (int) -StockMovement::query()
                ->where('type', 'sale')
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('quantity'),
        ];
    }

    /**
     * @return Collection<int, LowStockRow>
     */
    public function lowStock(): Collection
    {
        return app(LowStockReport::class)->rows($this->shop());
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function openOrders(): Collection
    {
        return PurchaseOrder::query()
            ->open()
            ->with('supplier', 'lines')
            ->orderByRaw('CASE WHEN expected_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expected_on')
            ->limit(6)
            ->get();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function recentMovements(): Collection
    {
        return StockMovement::query()
            ->with('variant.product', 'location', 'user')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
