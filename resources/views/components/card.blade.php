@props(['title' => null, 'padding' => true])

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 sm:px-5">
            <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
            @isset($actions)
                <div class="flex items-center gap-2 text-sm">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div @class(['p-4 sm:p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
