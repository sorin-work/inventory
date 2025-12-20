<div>
    <x-page-header title="Transfer stock" description="Move stock between your locations. The value moves with it at average cost." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form wire:submit="save" class="space-y-5">
                @include('livewire.stock.partials.variant-picker')

                <div class="grid items-end gap-4 sm:grid-cols-[1fr_1fr_8rem]">
                    @include('livewire.stock.partials.location-select', ['field' => 'locationId', 'label' => 'From'])

                    @include('livewire.stock.partials.location-select', ['field' => 'toLocationId', 'label' => 'To'])

                    <x-field label="Quantity" for="quantity" error="quantity">
                        <x-text-input id="quantity" type="number" min="1" step="1" wire:model="quantity" class="block w-full" />
                    </x-field>
                </div>

                <x-field label="Note" for="note" error="note">
                    <x-text-input id="note" type="text" wire:model="note" class="block w-full" />
                </x-field>

                <div class="flex justify-end">
                    <x-primary-button wire:loading.attr="disabled">Transfer</x-primary-button>
                </div>
            </form>
        </x-card>

        @include('livewire.stock.partials.recent')
    </div>
</div>
