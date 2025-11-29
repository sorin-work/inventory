@props(['color' => 'bg-gray-50 text-gray-600 ring-gray-500/20'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$color]) }}>{{ $slot }}</span>
