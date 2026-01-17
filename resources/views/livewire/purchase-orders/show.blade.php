@php
    $isOwner = auth()->user()->isOwner();
    $status = $order->status;
@endphp

<div>
    <x-page-header :title="$order->number">
        <x-slot name="breadcrumb">
            <a href="{{ route('purchase-orders.index') }}" wire:navigate class="hover:underline">Purchase orders</a>
        </x-slot>
        <x-slot name="actions">
            <x-badge :color="$status->badgeClasses()" class="me-1 text-sm">{{ $status->label() }}</x-badge>
            @if ($status->isEditable())
                <x-link-button variant="secondary" href="{{ route('purchase-orders.edit', $order) }}">Edit</x-link-button>
                <x-primary-button type="button" wire:click="placeOrder" wire:confirm="Mark {{ $order->number }} as sent to {{ $order->supplier->name }}?">Place order</x-primary-button>
            @endif
            @if ($status->canReceive() && ! $receiving)
                <x-primary-button type="button" wire:click="startReceiving">Receive delivery</x-primary-button>
            @endif
            @if ($isOwner && in_array($status->value, ['draft', 'ordered'], true) && ! $order->hasReceipts())
                <x-secondary-button wire:click="cancelOrder" wire:confirm="Cancel {{ $order->number }}?">Cancel order</x-secondary-button>
            @endif
        </x-slot>
    </x-page-header>

    @error('order')
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>
    @enderror

    <dl class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Supplier" :value="$order->supplier->name" :hint="$order->supplier->email" />
        <x-stat label="Deliver to" :value="$order->location->name" />
        <x-stat label="Expected" :value="$order->expected_on?->toFormattedDateString() ?? '—'" :hint="$order->ordered_at ? 'Ordered '.$order->ordered_at->toFormattedDateString() : null" />
        <x-stat label="Total" :value="$this->money($order->totalCents())" :hint="$order->lines->count().' '.Str::plural('line', $order->lines->count())" />
    </dl>

    <form wire:submit="saveReceipt">
        <x-card :title="$receiving ? 'Receive delivery into '.$order->location->name : 'Lines'" :padding="false" class="mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Product</x-th>
                            <x-th align="right">Ordered</x-th>
                            <x-th align="right">Received</x-th>
                            <x-th align="right">Unit cost</x-th>
                            <x-th align="right">Line total</x-th>
                            @if ($receiving)
                                <x-th align="right">Receiving now</x-th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($order->lines as $line)
                            <tr wire:key="line-{{ $line->id }}">
                                <x-td>
                                    <a href="{{ route('products.show', $line->variant->product_id) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $line->variant->displayName() }}</a>
                                    <div class="text-xs text-gray-500">{{ $line->variant->sku }}</div>
                                </x-td>
                                <x-td align="right" class="tabular-nums">{{ $line->quantity_ordered }}</x-td>
                                <x-td align="right" class="tabular-nums {{ $line->isFullyReceived() ? 'text-emerald-700' : 'text-gray-700' }}">{{ $line->quantity_received }}</x-td>
                                <x-td align="right" class="tabular-nums text-gray-700">{{ $this->money($line->unit_cost_cents) }}</x-td>
                                <x-td align="right" class="tabular-nums text-gray-900">{{ $this->money($line->totalCents()) }}</x-td>
                                @if ($receiving)
                                    <td class="px-4 py-2 text-right">
                                        @if ($line->remaining() > 0)
                                            <x-text-input type="number" min="0" max="{{ $line->remaining() }}" wire:model="receive.{{ $line->id }}" class="w-24 text-right" />
                                            <x-input-error :messages="$errors->get('receive.'.$line->id)" class="mt-1" />
                                        @else
                                            <span class="text-xs text-emerald-700">Done</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($receiving)
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-4 py-3">
                    <span class="me-auto text-xs text-gray-500">Short delivery? Enter what arrived; the rest stays on order.</span>
                    <x-secondary-button wire:click="cancelReceiving">Cancel</x-secondary-button>
                    <x-primary-button wire:loading.attr="disabled">Book in stock</x-primary-button>
                </div>
            @endif
        </x-card>
    </form>

    <p class="mt-6 text-xs text-gray-500">
        Created {{ $order->created_at->toFormattedDateString() }}@if ($order->user) by {{ $order->user->name }}@endif.
        @if ($order->received_at) Fully received {{ $order->received_at->toFormattedDateString() }}. @endif
    </p>
</div>
