@props(['label', 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes }}>
    <x-input-label :for="$for" :value="$label" />
    <div class="mt-1">{{ $slot }}</div>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    @if ($error)
        <x-input-error :messages="$errors->get($error)" class="mt-1" />
    @endif
</div>
