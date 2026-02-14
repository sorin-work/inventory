<div>
    <x-page-header title="Products" description="Everything you stock, with what's on hand across all locations.">
        <x-slot name="actions">
            <x-link-button href="{{ route('products.create') }}">New product</x-link-button>
        </x-slot>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <x-text-input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, SKU or barcode" class="lg:col-span-2" />

        <x-select wire:model.live="categoryId">
            <option value="">All categories</option>
            @foreach ($this->categories() as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-select>

        <x-select wire:model.live="supplierId">
            <option value="">All suppliers</option>
            @foreach ($this->suppliers() as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </x-select>

        <x-select wire:model.live="status">
            <option value="active">Active</option>
            <option value="low">Low stock</option>
            <option value="archived">Archived</option>
            <option value="all">All</option>
        </x-select>
    </div>

    @php $products = $this->products(); @endphp

    <x-card :padding="false">
        @if ($products->isEmpty())
            @if ($search !== '' || $categoryId !== '' || $supplierId !== '' || $status !== 'active')
                <x-empty-state title="No products match these filters" />
            @else
                <x-empty-state title="No products yet" description="Add the things you sell to start tracking their stock.">
                    <x-link-button href="{{ route('products.create') }}">New product</x-link-button>
                </x-empty-state>
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Product</x-th>
                            <x-th>Category</x-th>
                            <x-th>Supplier</x-th>
                            <x-th align="right">Price</x-th>
                            <x-th align="right">On hand</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($products as $product)
                            @php
                                $prices = $product->variants->pluck('price_cents');
                                $low = $product->variants->contains(fn ($v) => $v->isLowStock());
                            @endphp
                            <tr wire:key="product-{{ $product->id }}" class="hover:bg-gray-50">
                                <x-td>
                                    <a href="{{ route('products.show', $product) }}" wire:navigate class="font-medium text-gray-900 hover:text-indigo-600">{{ $product->name }}</a>
                                    <div class="text-xs text-gray-500">
                                        @if ($product->variants_count === 1)
                                            {{ $product->variants->first()?->sku }}
                                        @else
                                            {{ $product->variants_count }} variants
                                        @endif
                                        @if ($product->isArchived())
                                            <x-badge class="ms-1">Archived</x-badge>
                                        @endif
                                    </div>
                                </x-td>
                                <x-td class="text-gray-600">{{ $product->category?->name ?? '—' }}</x-td>
                                <x-td class="text-gray-600">{{ $product->supplier?->name ?? '—' }}</x-td>
                                <x-td align="right" class="tabular-nums text-gray-700">
                                    @if ($prices->isEmpty())
                                        —
                                    @elseif ($prices->min() === $prices->max())
                                        {{ $this->money($prices->min()) }}
                                    @else
                                        {{ $this->money($prices->min()) }}–{{ $this->money($prices->max()) }}
                                    @endif
                                </x-td>
                                <x-td align="right" class="tabular-nums">
                                    <span @class(['font-medium', 'text-rose-700' => $low, 'text-gray-900' => ! $low])>{{ number_format($product->on_hand) }}</span>
                                    <span class="text-xs text-gray-500">{{ $product->unit }}</span>
                                    @if ($low)
                                        <x-badge color="bg-rose-50 text-rose-700 ring-rose-600/20" class="ms-1">Low</x-badge>
                                    @endif
                                </x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $products->links() }}</div>
            @endif
        @endif
    </x-card>
</div>
