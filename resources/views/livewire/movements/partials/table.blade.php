@if ($movements->isEmpty())
    <x-empty-state title="No movements yet" description="Receiving, selling, adjusting or transferring stock adds a line here." />
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-th>Date</x-th>
                    <x-th>Type</x-th>
                    @if ($showProduct)
                        <x-th>{{ ($variantOnly ?? false) ? 'Variant' : 'Product' }}</x-th>
                    @endif
                    <x-th>Location</x-th>
                    <x-th align="right">Change</x-th>
                    <x-th align="right">Balance</x-th>
                    <x-th align="right">Unit cost</x-th>
                    <x-th>Details</x-th>
                    <x-th>By</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($movements as $movement)
                    <tr wire:key="movement-{{ $movement->id }}">
                        <x-td class="whitespace-nowrap text-gray-600" title="{{ $movement->created_at->toDayDateTimeString() }}">{{ $movement->created_at->format('M j, Y H:i') }}</x-td>
                        <x-td><x-badge :color="$movement->type->badgeClasses()">{{ $movement->type->label() }}</x-badge></x-td>
                        @if ($showProduct)
                            <x-td>
                                @if ($variantOnly ?? false)
                                    <span class="text-gray-700">{{ $movement->variant->optionLabel() ?: $movement->variant->sku }}</span>
                                @else
                                    <a href="{{ route('products.show', $movement->variant->product_id) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $movement->variant->displayName() }}</a>
                                    <div class="text-xs text-gray-500">{{ $movement->variant->sku }}</div>
                                @endif
                            </x-td>
                        @endif
                        <x-td class="whitespace-nowrap text-gray-700">{{ $movement->location->name }}</x-td>
                        <x-td align="right" class="tabular-nums font-medium {{ $movement->quantity > 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</x-td>
                        <x-td align="right" class="tabular-nums text-gray-700">{{ $movement->balance_after }}</x-td>
                        <x-td align="right" class="whitespace-nowrap tabular-nums text-gray-600">{{ $movement->unit_cost !== null ? $this->money($movement->unit_cost) : '—' }}</x-td>
                        <x-td class="text-gray-600">
                            @if ($movement->reason)
                                <span class="font-medium">{{ $movement->reason->label() }}</span>
                            @endif
                            @if ($movement->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $movement->purchaseOrder) }}" wire:navigate class="text-indigo-600 hover:underline">{{ $movement->purchaseOrder->number }}</a>
                            @endif
                            @if ($movement->unit_price_cents !== null)
                                <span>at {{ $this->money($movement->unit_price_cents) }}</span>
                            @endif
                            @if ($movement->note && ! $movement->purchaseOrder)
                                @if ($movement->reason || $movement->unit_price_cents !== null)
                                    <span class="text-gray-400">·</span>
                                @endif
                                <span class="text-gray-500">{{ $movement->note }}</span>
                            @endif
                        </x-td>
                        <x-td class="whitespace-nowrap text-gray-500">{{ $movement->user?->name ?? '—' }}</x-td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($movements->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $movements->links() }}</div>
    @endif
@endif
