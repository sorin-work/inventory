@props(['label', 'value', 'hint' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white px-4 py-4 shadow-sm']) }}>
    <dt class="text-sm text-gray-500">{{ $label }}</dt>
    <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">
        @if ($href)
            <a href="{{ $href }}" wire:navigate class="hover:text-indigo-600">{{ $value }}</a>
        @else
            {{ $value }}
        @endif
    </dd>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
