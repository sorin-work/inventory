@props(['variant' => 'primary'])

@php
    $classes = match ($variant) {
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500 border-transparent',
        'secondary' => 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300',
        default => 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300',
    };
@endphp

<a {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium shadow-sm '.$classes]) }} wire:navigate>
    {{ $slot }}
</a>
