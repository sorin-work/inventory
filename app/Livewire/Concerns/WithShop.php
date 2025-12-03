<?php

namespace App\Livewire\Concerns;

use App\Models\Shop;
use App\Models\User;
use App\Support\Money;

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

    public function money(int|float|null $cents): string
    {
        return Money::format($cents, $this->shop()->currency);
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
