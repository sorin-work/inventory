@php $rows = $this->rows(); @endphp

<div>
    <x-page-header title="Low stock" description="Variants at or below their reorder point, counting stock across all locations. Stock already on order is taken into account." />

    <x-card :padding="false">
        @if ($rows->isEmpty())
            <x-empty-state title="Nothing is running low" description="Set a reorder point on products you restock and they'll show up here when they need ordering." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Product</x-th>
                            <x-th>Supplier</x-th>
                            <x-th align="right">On hand</x-th>
                            <x-th align="right">Reorder at</x-th>
                            <x-th align="right">On order</x-th>
                            <x-th align="right">Suggested</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $row)
                            @php $variant = $row->variant; @endphp
                            <tr wire:key="low-{{ $variant->id }}">
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
                                <x-td align="right" class="tabular-nums font-medium">{{ $row->suggestedQuantity() ?: '—' }}</x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
