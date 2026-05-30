<?php

namespace App\Livewire\PurchaseOrders;

use App\Livewire\Concerns\WithShop;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    #[Url(as: 'supplier', except: '')]
    public string $supplierId = '';

    #[Url(except: '')]
    public string $search = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function orders(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'location', 'lines'])
            ->when($this->status === 'open', fn (Builder $q) => $q->open())
            ->when(in_array($this->status, ['draft', 'received', 'cancelled'], true), fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->supplierId !== '', fn (Builder $q) => $q->where('supplier_id', $this->supplierId))
            ->when(trim($this->search) !== '', fn (Builder $q) => $q->whereContains('number', $this->search))
            ->orderByRaw('CASE WHEN expected_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy($this->status === 'open' ? 'expected_on' : 'created_at', $this->status === 'open' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(25);
    }

    /**
     * @return Collection<int, Supplier>
     */
    public function suppliers(): Collection
    {
        return Supplier::query()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.purchase-orders.index');
    }
}
