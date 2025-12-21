@php
    $locations = $this->locations();
    $matrix = $this->matrix();
    $isOwner = auth()->user()->isOwner();
@endphp

<div>
    <x-page-header :title="$product->name">
        <x-slot name="breadcrumb">
            <a href="{{ route('products.index') }}" wire:navigate class="hover:underline">Products</a>
            @if ($product->category)
                / {{ $product->category->name }}
            @endif
        </x-slot>
        <x-slot name="actions">
            @if ($product->isArchived())
                <x-badge class="me-2">Archived {{ $product->archived_at->toFormattedDateString() }}</x-badge>
            @endif
            <x-link-button variant="secondary" href="{{ route('products.edit', $product) }}">Edit</x-link-button>
            @if ($isOwner)
                <div x-data="{ open: false }" class="relative">
                    <x-secondary-button @click="open = ! open" @click.outside="open = false">More</x-secondary-button>
                    <div x-show="open" x-transition style="display: none;" class="absolute right-0 z-20 mt-1 w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        @if ($product->isArchived())
                            <button wire:click="restore" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50">Restore</button>
                        @else
                            <button wire:click="archive" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50">Archive</button>
                        @endif
                        @if ($this->canDelete())
                            <button wire:click="delete" wire:confirm="Delete {{ $product->name }}? This cannot be undone." class="block w-full px-3 py-2 text-left text-sm text-rose-600 hover:bg-gray-50">Delete</button>
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>
    </x-page-header>

    <dl class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-3">
        <x-stat label="On hand" :value="number_format($product->variants->sum(fn ($v) => array_sum($matrix[$v->id] ?? []))).' '.$product->unit" />
        <x-stat label="Stock value" :value="$this->money($this->stockValue())" hint="At average cost" />
        <x-stat label="Supplier" :value="$product->supplier?->name ?? '—'" />
    </dl>

    @if ($product->description)
        <p class="mb-6 max-w-3xl whitespace-pre-line text-sm text-gray-600">{{ $product->description }}</p>
    @endif

    <x-card title="Stock by location" :padding="false" class="mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <x-th>Variant</x-th>
                        @foreach ($locations as $location)
                            <x-th align="right">{{ $location->name }}</x-th>
                        @endforeach
                        <x-th align="right">Total</x-th>
                        <x-th align="right">Price</x-th>
                        <x-th align="right">Avg cost</x-th>
                        <x-th><span class="sr-only">Actions</span></x-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($product->variants as $variant)
                        @php $total = array_sum($matrix[$variant->id] ?? []); @endphp
                        <tr wire:key="variant-{{ $variant->id }}">
                            <x-td>
                                <div class="font-medium text-gray-900">{{ $variant->optionLabel() ?: 'Default' }}</div>
                                <div class="text-xs text-gray-500">{{ $variant->sku }}@if ($variant->barcode) · {{ $variant->barcode }}@endif</div>
                            </x-td>
                            @foreach ($locations as $location)
                                <x-td align="right" class="tabular-nums text-gray-700">{{ $matrix[$variant->id][$location->id] ?? 0 }}</x-td>
                            @endforeach
                            <x-td align="right" class="tabular-nums">
                                <span class="font-semibold">{{ $total }}</span>
                            </x-td>
                            <x-td align="right" class="tabular-nums text-gray-700">{{ $this->money($variant->price_cents) }}</x-td>
                            <x-td align="right" class="tabular-nums text-gray-700">{{ $this->money($variant->average_cost) }}</x-td>
                            <x-td align="right" class="whitespace-nowrap text-xs">
                                @unless ($product->isArchived())
                                    <a href="{{ route('stock.receive') }}" wire:navigate class="text-indigo-600 hover:underline">Receive</a>
                                    <a href="{{ route('stock.sell') }}" wire:navigate class="ms-2 text-indigo-600 hover:underline">Sell</a>
                                    <a href="{{ route('stock.adjust') }}" wire:navigate class="ms-2 text-indigo-600 hover:underline">Adjust</a>
                                    @if ($locations->count() > 1)
                                        <a href="{{ route('stock.transfer') }}" wire:navigate class="ms-2 text-indigo-600 hover:underline">Transfer</a>
                                    @endif
                                @endunless
                            </x-td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    @php $movements = $this->movements(); @endphp

    <x-card title="Movement ledger" :padding="false">
        @include('livewire.movements.partials.table', ['movements' => $movements, 'showProduct' => $product->variants->count() > 1, 'variantOnly' => true])
    </x-card>
</div>
