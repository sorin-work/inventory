<div>
    <x-page-header title="Shop settings" />

    <x-card class="max-w-xl">
        <form wire:submit="save" class="space-y-4">
            <x-field label="Shop name" for="name" error="name">
                <x-text-input id="name" wire:model="name" class="block w-full" required />
            </x-field>

            <x-field label="Currency" for="currency" error="currency" hint="Only changes how amounts are shown. Existing prices and costs are not converted.">
                <x-select id="currency" wire:model="currency" class="block w-full">
                    @foreach (\App\Support\Money::currencies() as $code => $label)
                        <option value="{{ $code }}">{{ $code }} · {{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <div class="flex justify-end">
                <x-primary-button>Save settings</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
