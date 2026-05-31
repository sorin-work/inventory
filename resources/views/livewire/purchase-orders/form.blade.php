<div>
    <x-page-header :title="$order ? 'Edit '.$order->number : 'New purchase order'">
        <x-slot name="breadcrumb">
            <a href="{{ route('purchase-orders.index') }}" wire:navigate class="hover:underline">Purchase orders</a>
        </x-slot>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <x-card title="Order">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field label="Supplier" for="supplierId" error="supplierId">
                    <x-select id="supplierId" wire:model.live="supplierId" class="block w-full">
                        <option value="">Choose a supplier…</option>
                        @foreach ($this->suppliers() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Deliver to" for="locationId" error="locationId">
                    <x-select id="locationId" wire:model="locationId" class="block w-full">
                        @foreach ($this->locations() as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Expected on" for="expectedOn" error="expectedOn">
                    <x-text-input id="expectedOn" type="date" wire:model="expectedOn" class="block w-full" />
                </x-field>

                <x-field label="Notes" for="notes" error="notes" class="sm:col-span-3">
                    <x-textarea id="notes" wire:model="notes" rows="2" class="block w-full" placeholder="Shown on the order, e.g. delivery instructions" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Lines" :padding="false">
            <div class="border-b border-gray-100 p-4">
                @include('livewire.stock.partials.variant-picker')
            </div>

            @if ($lines === [])
                <p class="px-4 py-6 text-center text-sm text-gray-500">Search above to add products to the order.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-th>Product</x-th>
                                <x-th>Quantity</x-th>
                                <x-th>Unit cost</x-th>
                                <x-th align="right">Line total</x-th>
                                <x-th><span class="sr-only">Remove</span></x-th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($lines as $i => $line)
                                <tr wire:key="line-{{ $line['product_variant_id'] }}" class="align-top">
                                    <x-td>
                                        <div class="font-medium text-gray-900">{{ $line['label'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $line['sku'] }}</div>
                                    </x-td>
                                    <td class="px-4 py-2">
                                        <x-text-input type="number" min="1" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" class="w-24" />
                                        <x-input-error :messages="$errors->get('lines.'.$i.'.quantity')" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <x-text-input inputmode="decimal" wire:model.live.debounce.400ms="lines.{{ $i }}.unit_cost" class="w-28" />
                                        <x-input-error :messages="$errors->get('lines.'.$i.'.unit_cost')" class="mt-1" />
                                    </td>
                                    <x-td align="right" class="tabular-nums text-gray-700">
                                        @php
                                            try { $lineTotal = (int) $line['quantity'] * \App\Support\Money::parse($line['unit_cost']); } catch (\InvalidArgumentException) { $lineTotal = 0; }
                                        @endphp
                                        {{ $this->money($lineTotal) }}
                                    </x-td>
                                    <x-td align="right">
                                        <button type="button" wire:click="removeLine({{ $i }})" class="text-sm text-rose-600 hover:underline">Remove</button>
                                    </x-td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <x-input-error :messages="$errors->get('lines')" class="px-4 pb-3" />
        </x-card>

        <div class="flex justify-end gap-3">
            <x-link-button variant="secondary" href="{{ $order ? route('purchase-orders.show', $order) : route('purchase-orders.index') }}">Cancel</x-link-button>
            <x-primary-button wire:loading.attr="disabled">Save draft</x-primary-button>
        </div>
    </form>
</div>
