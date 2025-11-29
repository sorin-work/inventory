<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <x-page-header title="Profile" />

    <div class="max-w-3xl space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-8">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
