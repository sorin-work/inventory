<?php

namespace App\Livewire\Concerns;

use App\Models\Shop;
use App\Models\User;

trait WithShop
{
    protected function user(): User
    {
        return auth()->user();
    }

    public function shop(): Shop
    {
        return $this->user()->shop;
    }

    protected function authorizeOwner(): void
    {
        abort_unless($this->user()->isOwner(), 403, 'Only shop owners can do that.');
    }

    protected function notify(string $message): void
    {
        $this->dispatch('notify', message: $message);
    }
}
