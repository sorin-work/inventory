<x-field :label="$label ?? 'Location'" :for="$field" :error="$field">
    <x-select id="{{ $field }}" wire:model.live="{{ $field }}" class="block w-full">
        <option value="">Choose…</option>
        @foreach ($this->locations() as $location)
            <option value="{{ $location->id }}">{{ $location->name }}</option>
        @endforeach
    </x-select>
</x-field>
