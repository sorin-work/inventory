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
            @can('manage-shop')
                <div x-data="{ open: false }" class="relative">
                    <x-secondary-button @click="open = ! open" @click.outside="open = false">More</x-secondary-button>
                    <div x-show="open" x-transition style="display: none;" class="absolute right-0 z-20 mt-1 w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                        @if ($product->isArchived())
                            <button wire:click="restore" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50">Restore</button>
                        @else
                            <button wire:click="archive" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50">Archive</button>
                        @endif
                        <button wire:click="delete" wire:confirm="Delete {{ $product->name }}? This cannot be undone." class="block w-full px-3 py-2 text-left text-sm text-rose-600 hover:bg-gray-50">Delete</button>
                    </div>
                </div>
            @endcan
        </x-slot>
    </x-page-header>

    @if ($product->description)
        <p class="mb-6 max-w-3xl whitespace-pre-line text-sm text-gray-600">{{ $product->description }}</p>
    @endif

    <x-card title="Variants" :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <x-th>Variant</x-th>
                        <x-th>SKU</x-th>
                        <x-th>Barcode</x-th>
                        <x-th align="right">Price</x-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($product->variants as $variant)
                        <tr wire:key="variant-{{ $variant->id }}">
                            <x-td class="font-medium text-gray-900">{{ $variant->optionLabel() ?: 'Default' }}</x-td>
                            <x-td class="text-gray-600">{{ $variant->sku }}</x-td>
                            <x-td class="text-gray-600">{{ $variant->barcode ?? '—' }}</x-td>
                            <x-td align="right" class="tabular-nums text-gray-700">{{ $this->money($variant->price_cents) }}</x-td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <p class="mt-4 text-xs text-gray-500">Supplier: {{ $product->supplier?->name ?? 'none' }} · Unit: {{ $product->unit }}</p>
</div>
