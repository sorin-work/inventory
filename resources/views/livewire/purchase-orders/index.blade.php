@php
    $orders = $this->orders();
    $counts = $this->counts();
    $tabs = ['open' => 'Open', 'draft' => 'Drafts', 'received' => 'Received', 'cancelled' => 'Cancelled', 'all' => 'All'];
@endphp

<div>
    <x-page-header title="Purchase orders" description="Orders to suppliers. Receiving an order adds the stock and updates average costs.">
        <x-slot name="actions">
            <x-link-button variant="secondary" href="{{ route('reports.low-stock') }}">From low stock</x-link-button>
            <x-link-button href="{{ route('purchase-orders.create') }}">New order</x-link-button>
        </x-slot>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <nav class="flex gap-1 overflow-x-auto text-sm">
            @foreach ($tabs as $value => $label)
                <button wire:click="$set('status', '{{ $value }}')" @class([
                    'whitespace-nowrap rounded-md px-3 py-1.5 font-medium',
                    'bg-white text-gray-900 shadow-sm ring-1 ring-gray-200' => $status === $value,
                    'text-gray-600 hover:text-gray-900' => $status !== $value,
                ])>
                    {{ $label }}
                    @if (isset($counts[$value]) && $counts[$value] > 0)
                        <span class="ms-1 rounded-full bg-gray-100 px-1.5 text-xs text-gray-600">{{ $counts[$value] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
        <div class="flex gap-2">
            <x-text-input type="search" wire:model.live.debounce.300ms="search" placeholder="PO number" class="w-36" />
            <x-select wire:model.live="supplierId">
                <option value="">All suppliers</option>
                @foreach ($this->suppliers() as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </x-select>
        </div>
    </div>

    <x-card :padding="false">
        @if ($orders->isEmpty())
            <x-empty-state title="No purchase orders here" description="Create one by hand or from the low stock report.">
                <x-link-button href="{{ route('purchase-orders.create') }}">New order</x-link-button>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Number</x-th>
                            <x-th>Supplier</x-th>
                            <x-th>Status</x-th>
                            <x-th>Deliver to</x-th>
                            <x-th>Expected</x-th>
                            <x-th align="right">Received</x-th>
                            <x-th align="right">Total</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            <tr wire:key="po-{{ $order->id }}" class="hover:bg-gray-50">
                                <x-td><a href="{{ route('purchase-orders.show', $order) }}" wire:navigate class="font-medium text-gray-900 hover:text-indigo-600">{{ $order->number }}</a></x-td>
                                <x-td class="text-gray-700">{{ $order->supplier->name }}</x-td>
                                <x-td><x-badge :color="$order->status->badgeClasses()">{{ $order->status->label() }}</x-badge></x-td>
                                <x-td class="text-gray-600">{{ $order->location->name }}</x-td>
                                <x-td class="whitespace-nowrap {{ $order->isOverdue() ? 'font-medium text-rose-700' : 'text-gray-600' }}">
                                    {{ $order->expected_on?->toFormattedDateString() ?? '—' }}
                                    @if ($order->isOverdue())
                                        <span class="text-xs">(late)</span>
                                    @endif
                                </x-td>
                                <x-td align="right" class="tabular-nums text-gray-600">{{ $order->lines->sum('quantity_received') }} / {{ $order->lines->sum('quantity_ordered') }}</x-td>
                                <x-td align="right" class="tabular-nums text-gray-900">{{ $this->money($order->totalCents()) }}</x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        @endif
    </x-card>
</div>
