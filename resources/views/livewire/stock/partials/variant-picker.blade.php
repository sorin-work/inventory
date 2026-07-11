@php $selected = $this->selectedVariant(); @endphp

<div>
    <x-input-label for="search" value="Product" />

    @if ($selected)
        <div class="mt-1 flex items-center justify-between rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-900">{{ $selected->displayName() }}</p>
                <p class="text-xs text-gray-500">{{ $selected->sku }}@if ($selected->barcode) · {{ $selected->barcode }}@endif</p>
            </div>
            <button type="button" wire:click="clearVariant" class="ms-3 text-sm text-indigo-700 hover:underline">Change</button>
        </div>
    @else
        <div class="relative mt-1" x-data x-init="$nextTick(() => $refs.search.focus())">
            <x-text-input
                x-ref="search"
                id="search"
                type="search"
                class="block w-full"
                placeholder="Scan a barcode or search by name or SKU"
                autocomplete="off"
                wire:model.live.debounce.250ms="search"
                wire:keydown.enter.prevent="pickFirstMatch"
            />

            @php $matches = $this->variantMatches(); @endphp
            @if ($matches->isNotEmpty())
                <ul class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                    @foreach ($matches as $match)
                        <li>
                            <button type="button" wire:click="selectVariant({{ $match->id }})" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-gray-900">{{ $match->displayName() }}</span>
                                    <span class="block text-xs text-gray-500">{{ $match->sku }}</span>
                                </span>
                                <span class="ms-3 shrink-0 text-xs text-gray-500">{{ $match->onHand() }} on hand</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @elseif (mb_strlen(trim($search)) >= 2)
                <p class="mt-1 text-xs text-gray-500">No active product matches “{{ $search }}”.</p>
            @endif
        </div>
    @endif

    <x-input-error :messages="$errors->get('variantId')" class="mt-1" />
</div>
