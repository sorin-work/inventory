@php $categories = $this->categories(); @endphp

<div>
    <x-page-header title="Categories" description="Group products for filtering and for the valuation report." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card :padding="false" class="lg:col-span-2">
            @if ($categories->isEmpty())
                <x-empty-state title="No categories yet" />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($categories as $category)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5" wire:key="category-{{ $category->id }}">
                            @if ($editingId === $category->id)
                                <form wire:submit="update" class="flex flex-1 items-start gap-2">
                                    <div class="flex-1">
                                        <x-text-input wire:model="editingName" class="block w-full" autofocus />
                                        <x-input-error :messages="$errors->get('editingName')" class="mt-1" />
                                    </div>
                                    <x-primary-button>Save</x-primary-button>
                                    <x-secondary-button wire:click="cancelEdit">Cancel</x-secondary-button>
                                </form>
                            @else
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    <button wire:click="edit({{ $category->id }})" class="text-indigo-600 hover:underline">Rename</button>
                                    @can('manage-shop')
                                        <button wire:click="delete({{ $category->id }})" wire:confirm="Delete {{ $category->name }}? Products keep existing without a category." class="text-rose-600 hover:underline">Delete</button>
                                    @endcan
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Add a category">
            <form wire:submit="create" class="space-y-3">
                <x-text-input wire:model="name" placeholder="e.g. Candles" class="block w-full" />
                <x-input-error :messages="$errors->get('name')" />
                <x-primary-button>Add category</x-primary-button>
            </form>
        </x-card>
    </div>
</div>
