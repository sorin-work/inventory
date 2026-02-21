<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithShop;
use App\Services\Reports\ValuationReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock valuation')]
class Valuation extends Component
{
    use WithShop;

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return app(ValuationReport::class)->summary($this->shop());
    }

    public function render()
    {
        return view('livewire.reports.valuation');
    }
}
