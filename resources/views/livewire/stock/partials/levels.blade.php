@if ($variantId)
    @php $levels = $this->levels(); @endphp
    <div class="rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-600">
        <span class="font-medium text-gray-700">On hand:</span>
        @foreach ($this->locations() as $location)
            <span class="ms-2 whitespace-nowrap">{{ $location->name }} <strong>{{ $levels[$location->id] ?? 0 }}</strong></span>
        @endforeach
    </div>
@endif
