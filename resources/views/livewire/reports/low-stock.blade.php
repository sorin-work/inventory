@php $rows = $this->rows(); @endphp

<div>
    <x-page-header title="Low stock" description="Variants at or below their reorder point, counting stock across all locations. Stock already on order is taken into account." />

    <form wire:submit="createOrders">
        <x-card :padding="false">
            @if ($rows->isEmpty())
                <x-empty-state title="Nothing is running low" description="Set a reorder point on products you restock and they'll show up here when they need ordering." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-th><span class="sr-only">Select</span></x-th>
                                <x-th>Product</x-th>
                                <x-th>Supplier</x-th>
                                <x-th align="right">On hand</x-th>
                                <x-th align="right">Reorder at</x-th>
                                <x-th align="right">On order</x-th>
                                <x-th align="right">Order</x-th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows as $row)
                                @php $variant = $row->variant; @endphp
                                <tr wire:key="low-{{ $variant->id }}">
                                    <td class="px-4 py-2.5">
                                        <input type="checkbox" value="{{ $variant->id }}" wire:model="selected" @disabled(! $variant->product->supplier_id) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40" />
                                    </td>
                                    <x-td>
                                        <a href="{{ route('products.show', $variant->product_id) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $variant->displayName() }}</a>
                                        <div class="text-xs text-gray-500">{{ $variant->sku }}</div>
                                    </x-td>
                                    <x-td class="text-gray-600">{{ $variant->product->supplier?->name ?? 'No supplier' }}</x-td>
                                    <x-td align="right" class="tabular-nums font-medium {{ $row->isOut() ? 'text-rose-700' : 'text-amber-700' }}">{{ $row->onHand }}</x-td>
                                    <x-td align="right" class="tabular-nums text-gray-600">{{ $variant->reorder_point }}</x-td>
                                    <x-td align="right" class="tabular-nums text-gray-600">
                                        {{ $row->onOrder ?: '—' }}
                                        @if ($row->isCoveredByOpenOrders())
                                            <div class="text-xs text-sky-700">covered</div>
                                        @endif
                                    </x-td>
                                    <td class="px-4 py-2 text-right">
                                        <x-text-input type="number" min="0" wire:model="order.{{ $variant->id }}" class="w-24 text-right" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-end">
                    <x-input-error :messages="$errors->get('selected')" class="sm:me-auto" />
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        Deliver to
                        <x-select wire:model="locationId">
                            @foreach ($this->locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </x-select>
                    </label>
                    <x-primary-button wire:loading.attr="disabled">Create draft orders</x-primary-button>
                </div>
            @endif
        </x-card>
    </form>

    <p class="mt-3 text-xs text-gray-500">One draft is created per supplier. Unit costs start at each product's average cost; check them against the supplier's price list before placing the order.</p>
</div>
