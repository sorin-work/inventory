<div>
    <x-page-header title="Team" description="Owners can do everything. Staff handle products, stock and purchase orders, but can't see the valuation report, change settings, manage the team or delete records." />

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card :padding="false" class="lg:col-span-2">
            <ul class="divide-y divide-gray-100">
                @foreach ($this->members() as $member)
                    <li class="flex items-center justify-between gap-3 px-4 py-3" wire:key="member-{{ $member->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">
                                {{ $member->name }}
                            </p>
                            <p class="truncate text-xs text-gray-500">{{ $member->email }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-select wire:change="changeRole({{ $member->id }}, $event.target.value)" class="py-1 text-xs" aria-label="Role">
                                @foreach (\App\Enums\Role::cases() as $role)
                                    <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                                @endforeach
                            </x-select>
                            @unless ($member->is(auth()->user()))
                                <button wire:click="remove({{ $member->id }})" wire:confirm="Remove {{ $member->name }} from the shop?" class="text-sm text-rose-600 hover:underline">Remove</button>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card title="Add someone">
            <form wire:submit="add" class="space-y-3">
                <x-field label="Name" for="member-name" error="name">
                    <x-text-input id="member-name" wire:model="name" class="block w-full" />
                </x-field>
                <x-field label="Email" for="member-email" error="email">
                    <x-text-input id="member-email" type="email" wire:model="email" class="block w-full" />
                </x-field>
                <x-field label="Temporary password" for="member-password" error="password" hint="Share it with them; they can change it from their profile.">
                    <x-text-input id="member-password" type="text" wire:model="password" class="block w-full" autocomplete="off" />
                </x-field>
                <x-field label="Role" for="member-role" error="role">
                    <x-select id="member-role" wire:model="role" class="block w-full">
                        @foreach (\App\Enums\Role::cases() as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-primary-button>Add to team</x-primary-button>
            </form>
        </x-card>
    </div>
</div>
