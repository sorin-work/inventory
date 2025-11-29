@props(['align' => 'left'])

<td {{ $attributes->merge(['class' => 'px-4 py-2.5 text-sm text-'.$align]) }}>{{ $slot }}</td>
