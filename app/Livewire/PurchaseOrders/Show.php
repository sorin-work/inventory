<?php

namespace App\Livewire\PurchaseOrders;

use App\Exceptions\PurchaseOrderException;
use App\Exceptions\StockException;
use App\Livewire\Concerns\WithShop;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithShop;

    public PurchaseOrder $order;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->order = $purchaseOrder;
    }

    public function placeOrder(PurchaseOrderService $orders): void
    {
        $this->run(fn () => $orders->markOrdered($this->order), 'Order placed. Receive it here when it arrives.');
    }

    public function cancelOrder(PurchaseOrderService $orders): void
    {
        $this->authorizeOwner();

        $this->run(fn () => $orders->cancel($this->order), 'Order cancelled.');
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
