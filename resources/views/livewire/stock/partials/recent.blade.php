@php $recent = $this->recent(); @endphp

<x-card title="Your recent entries" :padding="false">
    @if ($recent->isEmpty())
        <p class="px-4 py-6 text-center text-sm text-gray-500">Nothing recorded yet.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach ($recent as $movement)
                <li class="px-4 py-2.5 text-sm" wire:key="recent-{{ $movement->id }}">
                    <div class="flex justify-between gap-2">
                        <a href="{{ route('products.show', $movement->variant->product_id) }}" wire:navigate class="truncate font-medium text-gray-900 hover:underline">{{ $movement->variant->displayName() }}</a>
                        <span @class(['shrink-0 font-medium tabular-nums', 'text-emerald-700' => $movement->quantity > 0, 'text-rose-700' => $movement->quantity < 0])>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                    </div>
                    <p class="text-xs text-gray-500">{{ $movement->location->name }} · {{ $movement->created_at->diffForHumans() }}</p>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
