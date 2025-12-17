<div>
    <x-page-header title="Record a sale" description="Takes stock out at the current average cost. Use this for sales made outside a till system." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form wire:submit="save" class="space-y-5">
                @include('livewire.stock.partials.variant-picker')

                <div class="grid gap-4 sm:grid-cols-3">
                    @include('livewire.stock.partials.location-select', ['field' => 'locationId', 'label' => 'From location'])

                    <x-field label="Quantity" for="quantity" error="quantity">
                        <x-text-input id="quantity" type="number" min="1" step="1" wire:model="quantity" class="block w-full" />
                    </x-field>

                    <x-field label="Unit price" for="unitPrice" error="unitPrice" hint="Leave as is to use the list price">
                        <x-text-input id="unitPrice" type="text" inputmode="decimal" wire:model="unitPrice" class="block w-full" placeholder="0.00" />
                    </x-field>
                </div>

                <x-field label="Note" for="note" error="note">
                    <x-text-input id="note" type="text" wire:model="note" class="block w-full" placeholder="Order number, customer…" />
                </x-field>

                <div class="flex justify-end">
                    <x-primary-button wire:loading.attr="disabled">Record sale</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
