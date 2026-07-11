<div>
    <x-page-header title="Receive stock" description="Book in deliveries that didn't come through a purchase order. The unit cost updates the product's average cost." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form wire:submit="save" class="space-y-5">
                @include('livewire.stock.partials.variant-picker')

                <div class="grid gap-4 sm:grid-cols-3">
                    @include('livewire.stock.partials.location-select', ['field' => 'locationId', 'label' => 'Into location'])

                    <x-field label="Quantity" for="quantity" error="quantity">
                        <x-text-input id="quantity" type="number" min="1" step="1" wire:model="quantity" class="block w-full" />
                    </x-field>

                    <x-field label="Unit cost" for="unitCost" error="unitCost" :hint="'In '.$this->shop()->currency">
                        <x-text-input id="unitCost" type="text" inputmode="decimal" wire:model="unitCost" class="block w-full" placeholder="0.00" />
                    </x-field>
                </div>

                @include('livewire.stock.partials.levels')

                <x-field label="Note" for="note" error="note">
                    <x-text-input id="note" type="text" wire:model="note" class="block w-full" placeholder="Delivery note, invoice number…" />
                </x-field>

                <div class="flex justify-end">
                    <x-primary-button wire:loading.attr="disabled">Receive</x-primary-button>
                </div>
            </form>
        </x-card>

        @include('livewire.stock.partials.recent')
    </div>
</div>
