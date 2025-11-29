<div>
    <x-page-header :title="$supplier ? 'Edit '.$supplier->name : 'New supplier'">
        <x-slot name="breadcrumb">
            <a href="{{ route('suppliers.index') }}" wire:navigate class="hover:underline">Suppliers</a>
        </x-slot>
    </x-page-header>

    <x-card class="max-w-3xl">
        <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" for="name" error="name" class="sm:col-span-2">
                <x-text-input id="name" wire:model="name" class="block w-full" required autofocus />
            </x-field>

            <x-field label="Contact person" for="contact_name" error="contact_name">
                <x-text-input id="contact_name" wire:model="contact_name" class="block w-full" />
            </x-field>

            <x-field label="Lead time (days)" for="lead_time_days" error="lead_time_days" hint="Typical days from ordering to delivery">
                <x-text-input id="lead_time_days" type="number" min="0" wire:model="lead_time_days" class="block w-full" />
            </x-field>

            <x-field label="Email" for="email" error="email">
                <x-text-input id="email" type="email" wire:model="email" class="block w-full" />
            </x-field>

            <x-field label="Phone" for="phone" error="phone">
                <x-text-input id="phone" wire:model="phone" class="block w-full" />
            </x-field>

            <x-field label="Address" for="address" error="address" class="sm:col-span-2">
                <x-textarea id="address" wire:model="address" rows="2" class="block w-full" />
            </x-field>

            <x-field label="Notes" for="notes" error="notes" class="sm:col-span-2">
                <x-textarea id="notes" wire:model="notes" class="block w-full" placeholder="Account number, minimum order, payment terms…" />
            </x-field>

            <div class="flex justify-end gap-3 sm:col-span-2">
                <x-link-button variant="secondary" href="{{ route('suppliers.index') }}">Cancel</x-link-button>
                <x-primary-button>{{ $supplier ? 'Save supplier' : 'Add supplier' }}</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
