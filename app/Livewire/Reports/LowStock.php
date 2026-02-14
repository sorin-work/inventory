<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithShop;
use App\Services\Reports\LowStockReport;
use App\Services\Reports\LowStockRow;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Low stock')]
class LowStock extends Component
{
    use WithShop;

    /**
     * @return Collection<int, LowStockRow>
     */
    public function rows(): Collection
    {
        return app(LowStockReport::class)->rows($this->shop());
    }

    public function render()
    {
        return view('livewire.reports.low-stock');
    }
}
