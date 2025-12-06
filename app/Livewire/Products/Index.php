<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Models\Category;
use App\Models\Product;
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
#[Title('Products')]
class Index extends Component
{
    use WithPagination, WithShop;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'supplier', except: '')]
    public string $supplierId = '';

    /** active, archived or all */
    #[Url(except: 'active')]
    public string $status = 'active';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'supplierId', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'supplier', 'variants'])
            ->withCount('variants')
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.trim($this->search).'%';

                $q->where(function (Builder $q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', $like)->orWhere('barcode', trim($this->search)));
                });
            })
            ->when($this->categoryId !== '', fn (Builder $q) => $q->where('category_id', $this->categoryId))
            ->when($this->supplierId !== '', fn (Builder $q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->status === 'active', fn (Builder $q) => $q->whereNull('archived_at'))
            ->when($this->status === 'archived', fn (Builder $q) => $q->whereNotNull('archived_at'));

        return $query->orderBy('name')->paginate(25);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
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
        return view('livewire.products.index');
    }
}
