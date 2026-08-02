@php $report = $this->report(); @endphp

<div>
    <x-page-header title="Stock valuation" description="What your stock on hand is worth at weighted average cost.">
        <x-slot name="actions">
            <x-select wire:model.live="locationId">
                <option value="">All locations</option>
                @foreach ($this->locations() as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </x-select>
        </x-slot>
    </x-page-header>

    <dl class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Stock value" :value="$this->money($report['total_cents'])" hint="At average cost" />
        <x-stat label="Units on hand" :value="number_format($report['total_units'])" />
        <x-stat label="Retail value" :value="$this->money($report['retail_cents'])" :hint="$report['retail_cents'] > 0 ? 'Margin '.round(100 * ($report['retail_cents'] - $report['total_cents']) / $report['retail_cents']).'% at list prices' : null" />
    </dl>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        @foreach (['by_location' => 'By location', 'by_category' => 'By category'] as $key => $title)
            <x-card :title="$title" :padding="false">
                @if ($report[$key]->isEmpty())
                    <p class="px-4 py-6 text-center text-sm text-gray-500">No stock on hand.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($report[$key] as $group)
                            @php $share = $report['total_cents'] > 0 ? $group->value_cents / $report['total_cents'] * 100 : 0; @endphp
                            <li class="px-4 py-2.5">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-900">{{ $group->name }}</span>
                                    <span class="tabular-nums text-gray-900">{{ $this->money($group->value_cents) }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 rounded-full bg-gray-100">
                                        <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ number_format($share, 1, '.', '') }}%"></div>
                                    </div>
                                    <span class="w-24 text-right text-xs text-gray-500">{{ number_format($group->units) }} units</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endforeach
    </div>

    <x-card title="By product" :padding="false">
        @if ($report['rows']->isEmpty())
            <x-empty-state title="No stock on hand" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Product</x-th>
                            <x-th>Category</x-th>
                            <x-th align="right">Units</x-th>
                            <x-th align="right">Avg cost</x-th>
                            <x-th align="right">Value</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($report['rows'] as $row)
                            <tr wire:key="val-{{ $row->variant_id }}">
                                <x-td>
                                    <a href="{{ route('products.show', $row->product_id) }}" wire:navigate class="font-medium text-gray-900 hover:underline">{{ $row->name }}</a>
                                    <div class="text-xs text-gray-500">{{ $row->sku }}</div>
                                </x-td>
                                <x-td class="text-gray-600">{{ $row->category }}</x-td>
                                <x-td align="right" class="tabular-nums">{{ number_format($row->units) }}</x-td>
                                <x-td align="right" class="tabular-nums text-gray-600">{{ $this->money($row->average_cost) }}</x-td>
                                <x-td align="right" class="tabular-nums font-medium">{{ $this->money($row->value_cents) }}</x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
