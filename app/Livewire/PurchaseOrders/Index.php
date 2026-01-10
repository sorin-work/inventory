<?php

namespace App\Livewire\PurchaseOrders;

use App\Enums\PurchaseOrderStatus;
use App\Livewire\Concerns\WithShop;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Purchase orders')]
class Index extends Component
{
    use WithPagination, WithShop;

    /** open, draft, received, cancelled or all */
    #[Url(except: 'open')]
    public string $status = 'open';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function orders(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'location', 'lines'])
            ->when($this->status === 'open', fn (Builder $q) => $q->whereIn('status', PurchaseOrderStatus::open()))
            ->when(in_array($this->status, ['draft', 'received', 'cancelled'], true), fn (Builder $q) => $q->where('status', $this->status))
            ->orderByRaw('CASE WHEN expected_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy($this->status === 'open' ? 'expected_on' : 'created_at', $this->status === 'open' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function render()
    {
        return view('livewire.purchase-orders.index');
    }
}
