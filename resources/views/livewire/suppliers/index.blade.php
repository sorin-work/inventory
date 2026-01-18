@php $suppliers = $this->suppliers(); @endphp

<div>
    <x-page-header title="Suppliers" description="Who you buy from. Lead times fill in expected dates on purchase orders.">
        <x-slot name="actions">
            <x-link-button href="{{ route('suppliers.create') }}">New supplier</x-link-button>
        </x-slot>
    </x-page-header>

    <x-card :padding="false">
        @if ($suppliers->isEmpty())
            <x-empty-state title="No suppliers yet">
                <x-link-button href="{{ route('suppliers.create') }}">New supplier</x-link-button>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-th>Name</x-th>
                            <x-th>Contact</x-th>
                            <x-th align="right">Lead time</x-th>
                            <x-th align="right">Products</x-th>
                            <x-th align="right">Open orders</x-th>
                            <x-th><span class="sr-only">Actions</span></x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($suppliers as $supplier)
                            <tr wire:key="supplier-{{ $supplier->id }}">
                                <x-td class="font-medium text-gray-900">{{ $supplier->name }}</x-td>
                                <x-td class="text-gray-600">
                                    {{ $supplier->contact_name ?? '—' }}
                                    @if ($supplier->email)
                                        <div class="text-xs"><a href="mailto:{{ $supplier->email }}" class="text-indigo-600 hover:underline">{{ $supplier->email }}</a></div>
                                    @endif
                                    @if ($supplier->phone)
                                        <div class="text-xs text-gray-500">{{ $supplier->phone }}</div>
                                    @endif
                                </x-td>
                                <x-td align="right" class="text-gray-600">{{ $supplier->lead_time_days ? $supplier->lead_time_days.' days' : '—' }}</x-td>
                                <x-td align="right" class="tabular-nums">
                                    <a href="{{ route('products.index', ['supplier' => $supplier->id]) }}" wire:navigate class="text-indigo-600 hover:underline">{{ $supplier->products_count }}</a>
                                </x-td>
                                <x-td align="right" class="tabular-nums">
                                    {{ $supplier->open_orders_count }}
                                </x-td>
                                <x-td align="right" class="whitespace-nowrap">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" wire:navigate class="text-sm text-indigo-600 hover:underline">Edit</a>
                                    @can('manage-shop')
                                        <button wire:click="delete({{ $supplier->id }})" wire:confirm="Delete {{ $supplier->name }}?" class="ms-3 text-sm text-rose-600 hover:underline">Delete</button>
                                    @endcan
                                </x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($suppliers->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $suppliers->links() }}</div>
            @endif
        @endif
    </x-card>
</div>
