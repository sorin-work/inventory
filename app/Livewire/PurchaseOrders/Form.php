<?php

namespace App\Livewire\PurchaseOrders;

use App\Exceptions\PurchaseOrderException;
use App\Livewire\Concerns\PicksVariant;
use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    use PicksVariant, WithShop;

    public ?PurchaseOrder $order = null;

    public string $supplierId = '';

    public string $locationId = '';

    public string $expectedOn = '';

    public string $notes = '';

    /** @var list<array{product_variant_id: int, label: string, sku: string, quantity: string, unit_cost: string}> */
    public array $lines = [];

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        if ($purchaseOrder?->exists) {
            abort_unless($purchaseOrder->status->isEditable(), 403, 'Only draft orders can be edited.');

            $this->order = $purchaseOrder;
            $this->supplierId = (string) $purchaseOrder->supplier_id;
            $this->locationId = (string) $purchaseOrder->location_id;
            $this->expectedOn = (string) $purchaseOrder->expected_on?->toDateString();
            $this->notes = (string) $purchaseOrder->notes;
            $this->lines = $purchaseOrder->lines()->with('variant.product')->get()->map(fn ($line) => [
                'product_variant_id' => $line->product_variant_id,
                'label' => $line->variant->displayName(),
                'sku' => $line->variant->sku,
                'quantity' => (string) $line->quantity_ordered,
                'unit_cost' => Money::toDecimal($line->unit_cost_cents),
            ])->all();

            return;
        }

        $this->order = null;
        $this->locationId = (string) $this->shop()->defaultLocation()?->id;

        $supplierId = request()->integer('supplier');

        if ($supplierId > 0 && Supplier::query()->whereKey($supplierId)->exists()) {
            $this->supplierId = (string) $supplierId;
            $this->updatedSupplierId();
        }
    }

    public function updatedSupplierId(): void
    {
        $supplier = Supplier::query()->find($this->supplierId);

        if ($supplier?->lead_time_days && $this->expectedOn === '') {
            $this->expectedOn = today()->addDays($supplier->lead_time_days)->toDateString();
        }
    }

    protected function variantSelected(ProductVariant $variant): void
    {
        $existing = collect($this->lines)->search(fn ($line) => $line['product_variant_id'] === $variant->id);

        if ($existing !== false) {
            $this->lines[$existing]['quantity'] = (string) ((int) $this->lines[$existing]['quantity'] + 1);
        } else {
            $this->lines[] = [
                'product_variant_id' => $variant->id,
                'label' => $variant->displayName(),
                'sku' => $variant->sku,
                'quantity' => (string) max(1, (int) $variant->reorder_quantity),
                'unit_cost' => Money::toDecimal($variant->average_cost),
            ];
        }

        $this->variantId = null;
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(PurchaseOrderService $orders): void
    {
        $shopId = $this->shop()->id;

        $validated = $this->validate([
            'supplierId' => ['required', Rule::exists('suppliers', 'id')->where('shop_id', $shopId)],
            'locationId' => ['required', Rule::exists('locations', 'id')->where('shop_id', $shopId)],
            'expectedOn' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where('shop_id', $shopId)],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:10000000', 'decimal:0,2'],
        ], [
            'lines.required' => 'Add at least one product.',
            'lines.min' => 'Add at least one product.',
        ], [
            'supplierId' => 'supplier',
            'locationId' => 'location',
            'expectedOn' => 'expected date',
            'lines.*.quantity' => 'quantity',
            'lines.*.unit_cost' => 'unit cost',
        ]);

        $lines = array_map(fn ($line) => [
            'product_variant_id' => (int) $line['product_variant_id'],
            'quantity_ordered' => (int) $line['quantity'],
            'unit_cost_cents' => Money::parse($line['unit_cost']),
        ], $validated['lines']);

        $details = [
            'expected_on' => $this->expectedOn ?: null,
            'notes' => trim($this->notes) ?: null,
        ];

        try {
            if ($this->order) {
                $order = $orders->updateDraft($this->order, $lines, [
                    ...$details,
                    'supplier_id' => (int) $this->supplierId,
                    'location_id' => (int) $this->locationId,
                ]);
            } else {
                $order = $orders->createDraft(
                    $this->shop(),
                    Supplier::query()->findOrFail($this->supplierId),
                    Location::query()->findOrFail($this->locationId),
                    $lines,
                    $details,
                    $this->user(),
                );
            }
        } catch (PurchaseOrderException $e) {
            $this->addError('lines', $e->getMessage());

            return;
        }

        session()->flash('status', "{$order->number} saved as a draft.");
        $this->redirectRoute('purchase-orders.show', $order, navigate: true);
    }

    public function total(): int
    {
        return (int) collect($this->lines)->sum(function ($line) {
            try {
                return (int) $line['quantity'] * Money::parse($line['unit_cost']);
            } catch (\InvalidArgumentException) {
                return 0;
            }
        });
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
        return view('livewire.purchase-orders.form')
            ->title($this->order ? 'Edit '.$this->order->number : 'New purchase order');
    }
}
