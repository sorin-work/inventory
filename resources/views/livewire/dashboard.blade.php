@php
    $stats = $this->stats();
    $openOrders = $this->openOrders();
    $recent = $this->recentMovements();
@endphp

<div>
    <x-page-header title="Dashboard" :description="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.Str::before(auth()->user()->name, ' ').'.'" />

    <dl class="mb-6 grid grid-cols-2 gap-4">
        <x-stat label="Products" :value="number_format($stats['variants'])" hint="Active variants" :href="route('products.index')" />
        <x-stat label="Units on hand" :value="number_format($stats['units'])" :hint="number_format($stats['sold_30d']).' sold in the last 30 days'" />
    </dl>

    <div class="grid gap-6 lg:grid-cols-2">
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
                            <span class="ms-3 shrink-0 text-xs text-gray-500">
                                {{ $order->expected_on ? 'Due '.$order->expected_on->format('M j') : $order->status->label() }}
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
