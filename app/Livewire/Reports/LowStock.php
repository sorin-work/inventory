<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithShop;
use App\Models\Category;
use App\Models\Location;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use App\Services\Reports\LowStockReport;
use App\Services\Reports\LowStockRow;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Low stock')]
class LowStock extends Component
{
    use WithShop;

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'supplier', except: '')]
    public string $supplierId = '';

    /** @var array<int, string> variant id => quantity to order */
    public array $order = [];

    /** @var list<int> */
    public array $selected = [];

    public string $locationId = '';

    public function mount(): void
    {
        $this->locationId = (string) $this->shop()->defaultLocation()?->id;
        $this->prefill();
    }

    public function updatedCategoryId(): void
    {
        $this->prefill();
    }

    public function updatedSupplierId(): void
    {
        $this->prefill();
    }

    /**
     * @return Collection<int, LowStockRow>
     */
    public function rows(): Collection
    {
        return app(LowStockReport::class)->rows(
            $this->shop(),
            $this->categoryId === '' ? null : (int) $this->categoryId,
            $this->supplierId === '' ? null : (int) $this->supplierId,
        );
    }

    public function createOrders(PurchaseOrderService $orders): void
    {
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'locationId' => ['required', Rule::exists('locations', 'id')->where('shop_id', $this->shop()->id)],
            'order.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [
            'selected.required' => 'Tick the products you want to order.',
        ], ['order.*' => 'quantity']);

        $quantities = collect($this->selected)
            ->mapWithKeys(fn ($id) => [(int) $id => (int) ($this->order[$id] ?? 0)])
            ->filter(fn ($qty) => $qty > 0)
            ->all();

        if ($quantities === []) {
            $this->addError('selected', 'Enter a quantity for the products you ticked.');

            return;
        }

        $result = $orders->draftsFromLowStock(
            $this->shop(),
            Location::query()->findOrFail($this->locationId),
            $quantities,
            $this->user(),
        );

        $created = $result['orders'];
        $message = $created->count() === 1
            ? "Draft {$created->first()->number} created."
            : "{$created->count()} draft orders created.";

        if ($result['skipped']->isNotEmpty()) {
            $message .= ' Skipped '.$result['skipped']->pluck('sku')->implode(', ').' because the product has no supplier.';
        }

        session()->flash('status', $message);

        if ($created->count() === 1) {
            $this->redirectRoute('purchase-orders.show', $created->first(), navigate: true);
        } else {
            $this->redirectRoute('purchase-orders.index', ['status' => 'draft'], navigate: true);
        }
    }

    private function prefill(): void
    {
        $rows = $this->rows();

        $this->order = $rows->mapWithKeys(fn (LowStockRow $row) => [$row->variant->id => (string) $row->suggestedQuantity()])->all();
        $this->selected = $rows
            ->filter(fn (LowStockRow $row) => $row->suggestedQuantity() > 0)
            ->map(fn (LowStockRow $row) => $row->variant->id)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderByDesc('is_default')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.reports.low-stock');
    }
}
