<?php

namespace App\Livewire\PurchaseOrders;

use App\Exceptions\PurchaseOrderException;
use App\Exceptions\StockException;
use App\Livewire\Concerns\WithShop;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Services\PurchaseOrderService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithShop;

    public PurchaseOrder $order;

    /** @var array<int, string> line id => quantity to receive now */
    public array $receive = [];

    public bool $receiving = false;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->order = $purchaseOrder;
    }

    public function placeOrder(PurchaseOrderService $orders): void
    {
        $this->run(fn () => $orders->markOrdered($this->order), 'Order placed. Receive it here when it arrives.');
    }

    public function startReceiving(): void
    {
        $this->receiving = true;
        $this->receive = $this->order->lines->mapWithKeys(fn ($line) => [$line->id => (string) $line->remaining()])->all();
    }

    public function cancelReceiving(): void
    {
        $this->receiving = false;
        $this->receive = [];
        $this->resetErrorBag();
    }

    public function saveReceipt(PurchaseOrderService $orders): void
    {
        $this->validate([
            'receive' => ['array'],
            'receive.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [], ['receive.*' => 'quantity']);

        $quantities = array_map(fn ($qty) => (int) $qty, array_filter($this->receive, fn ($qty) => $qty !== '' && $qty !== null));

        $done = $this->run(fn () => $orders->receive($this->order, $quantities, $this->user()), null);

        if ($done) {
            $this->order->refresh();
            $this->receiving = false;
            $this->receive = [];
            $this->notify($this->order->status->value === 'received' ? 'Order fully received.' : 'Delivery booked in. The rest is still on order.');
        }
    }

    public function closeShort(PurchaseOrderService $orders): void
    {
        $this->run(fn () => $orders->closeShort($this->order), 'Order closed. The rest is no longer expected.');
    }

    public function duplicate(PurchaseOrderService $orders): void
    {
        $copy = $orders->duplicate($this->order, $this->user());

        session()->flash('status', "{$copy->number} created from {$this->order->number}.");
        $this->redirectRoute('purchase-orders.show', $copy, navigate: true);
    }

    public function cancelOrder(PurchaseOrderService $orders): void
    {
        $this->authorizeOwner();

        $this->run(fn () => $orders->cancel($this->order), 'Order cancelled.');
    }

    public function delete(): void
    {
        $this->authorizeOwner();

        if (! $this->order->status->isEditable()) {
            $this->addError('order', 'Only drafts can be deleted. Cancel the order instead.');

            return;
        }

        $number = $this->order->number;
        $this->order->delete();

        session()->flash('status', "{$number} deleted.");
        $this->redirectRoute('purchase-orders.index', navigate: true);
    }

    /**
     * Receipts booked against this order, newest first.
     *
     * @return Collection<int, StockMovement>
     */
    public function receipts(): Collection
    {
        return $this->order->movements()->with('variant.product', 'user')->latestFirst()->get();
    }

    private function run(callable $action, ?string $message): bool
    {
        try {
            $action();
        } catch (PurchaseOrderException|StockException $e) {
            $this->addError('order', $e->getMessage());

            return false;
        }

        $this->order->refresh();

        if ($message !== null) {
            $this->notify($message);
        }

        return true;
    }

    public function render()
    {
        $this->order->load(['supplier', 'location', 'user', 'lines.variant.product']);

        return view('livewire.purchase-orders.show')->title($this->order->number);
    }
}
