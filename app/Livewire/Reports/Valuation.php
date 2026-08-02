<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Services\Reports\ValuationReport;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock valuation')]
class Valuation extends Component
{
    use WithShop;

    #[Url(as: 'location', except: '')]
    public string $locationId = '';

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return app(ValuationReport::class)->summary(
            $this->shop(),
            $this->locationId === '' ? null : (int) $this->locationId,
        );
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.reports.valuation');
    }
}
