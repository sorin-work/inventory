<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();
    $sections = [
        [
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                ['label' => 'Products', 'route' => 'products.index', 'match' => 'products.*'],
            ],
        ],
        [
            'title' => 'Stock',
            'items' => [
                ['label' => 'Receive', 'route' => 'stock.receive', 'match' => 'stock.receive'],
                ['label' => 'Sell', 'route' => 'stock.sell', 'match' => 'stock.sell'],
                ['label' => 'Adjust or count', 'route' => 'stock.adjust', 'match' => 'stock.adjust'],
            ],
        ],
        [
            'title' => 'Setup',
            'items' => [
                ['label' => 'Suppliers', 'route' => 'suppliers.index', 'match' => 'suppliers.*'],
                ['label' => 'Categories', 'route' => 'categories.index', 'match' => 'categories.*'],
                ['label' => 'Locations', 'route' => 'locations.index', 'match' => 'locations.*'],
            ],
        ],
    ];
@endphp

<div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white">
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 px-5">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                <x-application-logo class="h-8 w-8 fill-current text-indigo-600" />
                <span class="truncate text-sm font-semibold text-gray-900">{{ $user->shop->name }}</span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @foreach ($sections as $section)
                <div class="mb-5">
                    @isset($section['title'])
                        <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $section['title'] }}</p>
                    @endisset
                    <ul class="space-y-0.5">
                        @foreach ($section['items'] as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" wire:navigate
                                   @class([
                                       'block rounded-md px-3 py-1.5 text-sm font-medium',
                                       'bg-indigo-50 text-indigo-700' => $active,
                                       'text-gray-700 hover:bg-gray-50 hover:text-gray-900' => ! $active,
                                   ])>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-4">
            <div class="text-sm font-medium text-gray-900" x-data="{{ json_encode(['name' => $user->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
            <div class="truncate text-xs text-gray-500">{{ $user->email }}</div>
            <div class="mt-3 flex gap-3 text-sm">
                <a href="{{ route('profile') }}" wire:navigate class="text-gray-600 hover:text-gray-900">Profile</a>
                <button wire:click="logout" class="text-gray-600 hover:text-gray-900">Log out</button>
            </div>
        </div>
    </aside>
</div>
