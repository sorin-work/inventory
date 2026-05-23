<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\WithShop;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Suppliers')]
class Index extends Component
{
    use WithPagination, WithShop;

    #[Url(except: '')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorizeOwner();

        $supplier = Supplier::query()->withCount('purchaseOrders')->findOrFail($id);

        if ($supplier->purchase_orders_count > 0) {
            $this->dispatch('notify', message: "{$supplier->name} has purchase orders and can't be deleted.");

            return;
        }

        $supplier->delete();
        $this->notify("{$supplier->name} deleted. Its products no longer have a supplier.");
    }

    public function suppliers(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return Supplier::query()
            ->withCount(['products', 'purchaseOrders as open_orders_count' => fn ($q) => $q->open()])
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereContains('name', $term)
                ->orWhereContains('contact_name', $term)
                ->orWhereContains('email', $term)))
            ->orderBy('name')
            ->paginate(25);
    }

    public function render()
    {
        return view('livewire.suppliers.index');
    }
}
