<div>
    <x-page-header title="Adjust or count stock" description="Fix stock that went missing, got damaged or came back. Counting sets the quantity to what's on the shelf." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form wire:submit="save" class="space-y-5">
                <div class="inline-flex rounded-md border border-gray-300 p-0.5 text-sm" role="tablist">
                    <button type="button" wire:click="$set('mode', 'change')" @class(['rounded px-3 py-1.5', 'bg-indigo-600 text-white' => $mode === 'change', 'text-gray-700' => $mode !== 'change'])>Add or remove</button>
                    <button type="button" wire:click="$set('mode', 'count')" @class(['rounded px-3 py-1.5', 'bg-indigo-600 text-white' => $mode === 'count', 'text-gray-700' => $mode !== 'count'])>Stock count</button>
                </div>

                @include('livewire.stock.partials.variant-picker')

                <div class="grid gap-4 sm:grid-cols-3">
                    @include('livewire.stock.partials.location-select', ['field' => 'locationId'])

                    @if ($mode === 'count')
                        <x-field label="Counted quantity" for="counted" error="counted" class="sm:col-span-2">
                            <x-text-input id="counted" type="number" min="0" step="1" wire:model="counted" class="block w-full" />
                        </x-field>
                    @else
                        <x-field label="Change" for="change" error="change" hint="Negative to remove, e.g. -2">
                            <x-text-input id="change" type="number" step="1" wire:model="change" class="block w-full" />
                        </x-field>

                        <x-field label="Reason" for="reason" error="reason">
                            <x-select id="reason" wire:model.live="reason" class="block w-full">
                                @foreach ($this->reasons() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                    @endif
                </div>

                @include('livewire.stock.partials.levels')

                <x-field label="Note" for="note" error="note" :hint="$mode === 'change' && $reason === 'other' ? 'Required for “Other”' : null">
                    <x-text-input id="note" type="text" wire:model="note" class="block w-full" />
                </x-field>

                <div class="flex justify-end">
                    <x-primary-button wire:loading.attr="disabled">{{ $mode === 'count' ? 'Save count' : 'Save adjustment' }}</x-primary-button>
                </div>
            </form>
        </x-card>

        @include('livewire.stock.partials.recent')
    </div>
</div>
