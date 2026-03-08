<div>
    <x-page-header :title="$product ? 'Edit product' : 'New product'">
        <x-slot name="breadcrumb">
            <a href="{{ route('products.index') }}" wire:navigate class="hover:underline">Products</a>
            @if ($product)
                / <a href="{{ route('products.show', $product) }}" wire:navigate class="hover:underline">{{ $product->name }}</a>
            @endif
        </x-slot>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <x-card title="Details">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Name" for="name" error="name" class="sm:col-span-2">
                    <x-text-input id="name" wire:model.blur="name" class="block w-full" required autofocus />
                </x-field>

                <x-field label="Category" for="categoryId" error="categoryId">
                    <div class="flex gap-2" x-data="{ adding: false }" x-on:category-created.window="adding = false">
                        <x-select id="categoryId" wire:model="categoryId" class="block w-full" x-show="! adding">
                            <option value="">No category</option>
                            @foreach ($this->categories() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </x-select>
                        <x-text-input x-show="adding" style="display: none;" wire:model="newCategory" wire:keydown.enter.prevent="createCategory" placeholder="New category name" class="block w-full" />
                        <x-secondary-button x-show="! adding" @click="adding = true">New</x-secondary-button>
                        <x-secondary-button x-show="adding" style="display: none;" wire:click="createCategory">Add</x-secondary-button>
                    </div>
                    <x-input-error :messages="$errors->get('newCategory')" class="mt-1" />
                </x-field>

                <x-field label="Supplier" for="supplierId" error="supplierId" hint="Used when creating purchase orders from the low stock report">
                    <x-select id="supplierId" wire:model="supplierId" class="block w-full">
                        <option value="">No supplier</option>
                        @foreach ($this->suppliers() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Unit" for="unit" error="unit">
                    <x-select id="unit" wire:model="unit" class="block w-full">
                        @foreach (\App\Models\Product::UNITS as $unitOption)
                            <option value="{{ $unitOption }}">{{ $unitOption }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field label="Description" for="description" error="description" class="sm:col-span-2">
                    <x-textarea id="description" wire:model="description" class="block w-full" />
                </x-field>
            </div>
        </x-card>

        <x-card title="Options">
            <x-slot name="actions">
                @if (count($options) < \App\Livewire\Products\Form::MAX_OPTIONS)
                    <button type="button" wire:click="addOption" class="text-indigo-600 hover:underline">Add option</button>
                @endif
            </x-slot>

            @if ($options === [])
                <p class="text-sm text-gray-500">No options. Add one if this product comes in sizes, colours or other variations.</p>
            @else
                <div class="space-y-3">
                    @foreach ($options as $i => $option)
                        <div class="grid gap-3 sm:grid-cols-[12rem_1fr_auto]" wire:key="option-{{ $i }}">
                            <div>
                                <x-text-input wire:model="options.{{ $i }}.name" placeholder="Size" class="block w-full" aria-label="Option name" />
                                <x-input-error :messages="$errors->get('options.'.$i.'.name')" class="mt-1" />
                            </div>
                            <x-text-input wire:model="options.{{ $i }}.values" placeholder="S, M, L, XL" class="block w-full" aria-label="Option values" />
                            <button type="button" wire:click="removeOption({{ $i }})" class="text-sm text-rose-600 hover:underline">Remove</button>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-3">
                        <x-secondary-button wire:click="generateVariants">Create variants from options</x-secondary-button>
                        <span class="text-xs text-gray-500">Keeps variants you already have.</span>
                    </div>
                    <x-input-error :messages="$errors->get('options')" />
                </div>
            @endif
        </x-card>

        <x-card :title="count($variants) === 1 && $options === [] ? 'SKU, price and reordering' : 'Variants'" :padding="false">
            <x-slot name="actions">
                <button type="button" wire:click="addVariant" class="text-indigo-600 hover:underline">Add variant</button>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($options as $option)
                                <x-th>{{ $option['name'] ?: 'Option' }}</x-th>
                            @endforeach
                            <x-th>SKU</x-th>
                            <x-th>Barcode</x-th>
                            <x-th>Price</x-th>
                            <x-th>Reorder at</x-th>
                            <x-th>Reorder qty</x-th>
                            <x-th><span class="sr-only">Remove</span></x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($variants as $i => $variant)
                            <tr wire:key="variant-{{ $variant['id'] ?? 'new-'.$i }}" class="align-top">
                                @foreach ($options as $o => $option)
                                    <td class="px-2 py-2">
                                        <x-text-input wire:model="variants.{{ $i }}.values.{{ $o }}" class="w-24" />
                                        <x-input-error :messages="$errors->get('variants.'.$i.'.values.'.$o)" class="mt-1" />
                                    </td>
                                @endforeach
                                <td class="px-2 py-2">
                                    <div class="flex items-center gap-1">
                                        <x-text-input wire:model="variants.{{ $i }}.sku" class="w-44 uppercase" />
                                        @if ($variant['sku'] === '')
                                            <button type="button" wire:click="suggestSku({{ $i }})" class="text-xs text-indigo-600 hover:underline" title="Suggest from the name">Suggest</button>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('variants.'.$i.'.sku')" class="mt-1 max-w-xs" />
                                </td>
                                <td class="px-2 py-2">
                                    <x-text-input wire:model="variants.{{ $i }}.barcode" class="w-40" inputmode="numeric" />
                                    <x-input-error :messages="$errors->get('variants.'.$i.'.barcode')" class="mt-1 max-w-xs" />
                                </td>
                                <td class="px-2 py-2">
                                    <x-text-input wire:model="variants.{{ $i }}.price" class="w-24" inputmode="decimal" placeholder="0.00" />
                                    <x-input-error :messages="$errors->get('variants.'.$i.'.price')" class="mt-1" />
                                </td>
                                <td class="px-2 py-2">
                                    <x-text-input type="number" min="0" wire:model="variants.{{ $i }}.reorder_point" class="w-20" />
                                    <x-input-error :messages="$errors->get('variants.'.$i.'.reorder_point')" class="mt-1" />
                                </td>
                                <td class="px-2 py-2">
                                    <x-text-input type="number" min="1" wire:model="variants.{{ $i }}.reorder_quantity" class="w-20" />
                                    <x-input-error :messages="$errors->get('variants.'.$i.'.reorder_quantity')" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 text-right">
                                    @if (count($variants) > 1)
                                        <button type="button" wire:click="removeVariant({{ $i }})" class="text-sm text-rose-600 hover:underline">Remove</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-input-error :messages="$errors->get('variants')" class="px-4 py-2" />
            <p class="border-t border-gray-100 px-4 py-3 text-xs text-gray-500">
                Leave “Reorder at” empty for products you don't restock. Stock is added on the receive screen or through purchase orders, never here.
            </p>
        </x-card>

        <div class="flex justify-end gap-3">
            <x-link-button variant="secondary" href="{{ $product ? route('products.show', $product) : route('products.index') }}">Cancel</x-link-button>
            <x-primary-button wire:loading.attr="disabled">{{ $product ? 'Save product' : 'Create product' }}</x-primary-button>
        </div>
    </form>
</div>
