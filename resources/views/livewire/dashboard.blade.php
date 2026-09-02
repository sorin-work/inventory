@php
    $stats = $this->stats();
    $lowStock = $this->lowStock();
    $openOrders = $this->openOrders();
    $recent = $this->recentMovements();
@endphp

<div>
    <x-page-header title="Dashboard" :description="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.Str::before(auth()->user()->name, ' ').'.'">
        <x-slot name="actions">
            <x-link-button variant="secondary" href="{{ route('stock.receive') }}">Receive</x-link-button>
            <x-link-button variant="secondary" href="{{ route('stock.sell') }}">Sell</x-link-button>
            <x-link-button href="{{ route('products.create') }}">New product</x-link-button>
        </x-slot>
    </x-page-header>

    <dl class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Products" :value="number_format($stats['variants'])" hint="Active variants" :href="route('products.index')" />
        <x-stat label="Units on hand" :value="number_format($stats['units'])" :hint="number_format($stats['sold_30d']).' sold in the last 30 days'" />
        @if ($stats['value_cents'] !== null)
            <x-stat label="Stock value" :value="$this->money($stats['value_cents'])" hint="At average cost" :href="route('reports.valuation')" />
        @else
            <x-stat label="Open orders" :value="$openOrders->count()" :href="route('purchase-orders.index')" />
        @endif
        <x-stat label="Low stock" :value="$lowStock->count()" :hint="$lowStock->filter->isOut()->count().' out of stock'" :href="route('reports.low-stock')" />
    </dl>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Running low" :padding="false">
            <x-slot name="actions">
                <a href="{{ route('reports.low-stock') }}" wire:navigate class="text-indigo-600 hover:underline">View all</a>
            </x-slot>
            @if ($lowStock->isEmpty())
                <p class="px-4 py-6 text-center text-sm text-gray-500">Nothing is below its reorder point.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($lowStock->take(6) as $row)
                        <li class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <a href="{{ route('products.show', $row->variant->product_id) }}" wire:navigate class="truncate font-medium text-gray-900 hover:underline">{{ $row->variant->displayName() }}</a>
                            <span class="ms-3 shrink-0 tabular-nums {{ $row->isOut() ? 'text-rose-700' : 'text-amber-700' }}">
                                {{ $row->onHand }} / {{ $row->variant->reorder_point }}
                                @if ($row->onOrder > 0)
                                    <span class="text-xs text-sky-700">+{{ $row->onOrder }} coming</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Open purchase orders" :padding="false">
            <x-slot name="actions">
                <a href="{{ route('purchase-orders.index') }}" wire:navigate class="text-indigo-600 hover:underline">View all</a>
            </x-slot>
            @if ($openOrders->isEmpty())
                <p class="px-4 py-6 text-center text-sm text-gray-500">No orders on the way.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($openOrders as $order)
                        <li class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <div class="min-w-0">
                                <a href="{{ route('purchase-orders.show', $order) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $order->number }}</a>
                                <span class="text-gray-500">· {{ $order->supplier->name }}</span>
                            </div>
                            <span class="ms-3 shrink-0 text-xs {{ $order->isOverdue() ? 'font-medium text-rose-700' : 'text-gray-500' }}">
                                {{ $order->expected_on ? ($order->isOverdue() ? 'Late, due ' : 'Due ').$order->expected_on->format('M j') : $order->status->label() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Recent stock movements" :padding="false" class="lg:col-span-2">
            <x-slot name="actions">
                <a href="{{ route('movements.index') }}" wire:navigate class="text-indigo-600 hover:underline">Ledger</a>
            </x-slot>
            @if ($recent->isEmpty())
                <x-empty-state title="No stock recorded yet" description="Start by adding products, then receive your opening stock.">
                    <x-link-button href="{{ route('stock.receive') }}">Receive stock</x-link-button>
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recent as $movement)
                        <li class="flex items-center gap-3 px-4 py-2.5 text-sm">
                            <x-badge :color="$movement->type->badgeClasses()" class="w-24 justify-center">{{ $movement->type->label() }}</x-badge>
                            <a href="{{ route('products.show', $movement->variant->product_id) }}" wire:navigate class="min-w-0 flex-1 truncate text-gray-900 hover:underline">{{ $movement->variant->displayName() }}</a>
                            <span class="hidden text-gray-500 sm:inline">{{ $movement->location->name }}</span>
                            <span class="w-14 text-right font-medium tabular-nums {{ $movement->quantity > 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                            <span class="hidden w-28 text-right text-xs text-gray-500 md:inline">{{ $movement->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>
