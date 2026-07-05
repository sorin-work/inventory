<div>
    <x-page-header title="Transfer stock" description="Move stock between your locations. The value moves with it at average cost." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            @if ($this->locations()->count() < 2)
                <x-empty-state title="You need two locations to transfer stock" description="Add a back room, warehouse or second shop first.">
                    <x-link-button href="{{ route('locations.index') }}">Manage locations</x-link-button>
                </x-empty-state>
            @else
                <form wire:submit="save" class="space-y-5">
                    @include('livewire.stock.partials.variant-picker')

                    <div class="grid items-end gap-4 sm:grid-cols-[1fr_auto_1fr_8rem]">
                        @include('livewire.stock.partials.location-select', ['field' => 'locationId', 'label' => 'From'])

                        <button type="button" wire:click="swap" class="mb-0.5 rounded-md border border-gray-300 px-2 py-2 text-gray-600 hover:bg-gray-50" title="Swap locations">⇄</button>

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
            @endif
        </x-card>

        @include('livewire.stock.partials.recent')
    </div>
</div>
