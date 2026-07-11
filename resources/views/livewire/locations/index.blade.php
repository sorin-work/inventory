@php $locations = $this->locations(); @endphp

<div>
    <x-page-header title="Locations" description="Places you keep stock: the shop floor, a back room, a warehouse, a market stall." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card :padding="false" class="lg:col-span-2">
            <ul class="divide-y divide-gray-100">
                @foreach ($locations as $location)
                    <li class="px-4 py-3" wire:key="location-{{ $location->id }}">
                        @if ($editingId === $location->id)
                            <form wire:submit="update" class="grid gap-2 sm:grid-cols-[1fr_8rem_auto_auto] sm:items-start">
                                <div>
                                    <x-text-input wire:model="editingName" class="block w-full" aria-label="Name" />
                                    <x-input-error :messages="$errors->get('editingName')" class="mt-1" />
                                </div>
                                <div>
                                    <x-text-input wire:model="editingCode" class="block w-full uppercase" aria-label="Code" placeholder="Code" />
                                    <x-input-error :messages="$errors->get('editingCode')" class="mt-1" />
                                </div>
                                <x-primary-button>Save</x-primary-button>
                                <x-secondary-button wire:click="cancelEdit">Cancel</x-secondary-button>
                            </form>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $location->name }}</span>
                                    @if ($location->code)
                                        <span class="ms-1 text-xs text-gray-500">{{ $location->code }}</span>
                                    @endif
                                    @if ($location->is_default)
                                        <x-badge color="bg-indigo-50 text-indigo-700 ring-indigo-600/20" class="ms-1">Default</x-badge>
                                    @endif
                                    <p class="text-xs text-gray-500">
                                        {{ number_format($location->units) }} units
                                        @can('manage-shop')
                                            · {{ $this->money($location->value_cents) }}
                                        @endcan
                                    </p>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    @unless ($location->is_default)
                                        <button wire:click="makeDefault({{ $location->id }})" class="text-gray-600 hover:underline">Make default</button>
                                    @endunless
                                    <button wire:click="edit({{ $location->id }})" class="text-indigo-600 hover:underline">Edit</button>
                                    @can('manage-shop')
                                        @if ($locations->count() > 1)
                                            <button wire:click="delete({{ $location->id }})" wire:confirm="Delete {{ $location->name }}?" class="text-rose-600 hover:underline">Delete</button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card title="Add a location">
            <form wire:submit="create" class="space-y-3">
                <x-field label="Name" for="name" error="name">
                    <x-text-input id="name" wire:model="name" placeholder="Back room" class="block w-full" />
                </x-field>
                <x-field label="Short code" for="code" error="code" hint="Optional, e.g. BACK">
                    <x-text-input id="code" wire:model="code" class="block w-full uppercase" />
                </x-field>
                <x-primary-button>Add location</x-primary-button>
            </form>
            <p class="mt-4 text-xs text-gray-500">The default location is pre-selected when receiving and selling stock, and on new purchase orders.</p>
        </x-card>
    </div>
</div>
