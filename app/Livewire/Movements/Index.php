<?php

namespace App\Livewire\Movements;

use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Movement ledger')]
class Index extends Component
{
    use WithPagination, WithShop;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function movements(): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['variant.product', 'location', 'user', 'purchaseOrder'])
            ->when(trim($this->search) !== '', function (Builder $q) {
                $q->whereHas('variant', fn (Builder $v) => $v->search($this->search));
            })
            ->when($this->type !== '', fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->location !== '', fn (Builder $q) => $q->where('location_id', $this->location))
            ->when($this->validDate($this->from), fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($this->from)->startOfDay()))
            ->when($this->validDate($this->to), fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($this->to)->endOfDay()))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(30);
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        return Location::query()->orderBy('name')->get();
    }

    public function exportUrl(): string
    {
        return route('movements.export', array_filter([
            'type' => $this->type,
            'location' => $this->location,
            'from' => $this->validDate($this->from) ? $this->from : null,
            'to' => $this->validDate($this->to) ? $this->to : null,
        ]));
    }

    private function validDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false;
    }

    public function render()
    {
        return view('livewire.movements.index');
    }
}
