@props(['align' => 'left'])

<th scope="col" {{ $attributes->merge(['class' => 'whitespace-nowrap px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 text-'.$align]) }}>{{ $slot }}</th>
