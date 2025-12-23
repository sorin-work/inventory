<div>
    <x-page-header title="Movement ledger" description="Every change to stock, newest first." />

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-select wire:model.live="type">
            <option value="">All types</option>
            @foreach (\App\Enums\MovementType::cases() as $movementType)
                <option value="{{ $movementType->value }}">{{ $movementType->label() }}</option>
            @endforeach
        </x-select>
        <x-select wire:model.live="location">
            <option value="">All locations</option>
            @foreach ($this->locations() as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
            @endforeach
        </x-select>
        <x-text-input type="date" wire:model.live="from" aria-label="From date" />
        <x-text-input type="date" wire:model.live="to" aria-label="To date" />
    </div>

    <x-card :padding="false">
        @include('livewire.movements.partials.table', ['movements' => $this->movements(), 'showProduct' => true])
    </x-card>
</div>
