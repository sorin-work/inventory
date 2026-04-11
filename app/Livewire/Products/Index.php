<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /** active, low, archived or all */
    #[Url(except: 'active')]
    public string $status = 'active';

    #[Url(except: 'name')]
    public string $sort = 'name';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'supplierId', 'status', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'categoryId', 'supplierId', 'status');
        $this->resetPage();
    }

    public function products(): LengthAwarePaginator
    {
        $onHand = DB::table('stock_levels')
            ->join('product_variants', 'product_variants.id', '=', 'stock_levels.product_variant_id')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->selectRaw('COALESCE(SUM(stock_levels.quantity), 0)');

        $query = Product::query()
            ->with(['category', 'supplier', 'variants' => fn ($q) => $q->withOnHand()])
            ->withCount('variants')
            ->addSelect(['on_hand' => $onHand])
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.trim($this->search).'%';

                $q->where(function (Builder $q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', $like)->orWhere('barcode', trim($this->search)));
                });
            })
            ->when($this->categoryId !== '', fn (Builder $q) => $q->where('category_id', $this->categoryId === 'none' ? null : $this->categoryId))
            ->when($this->supplierId !== '', fn (Builder $q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->status === 'active', fn (Builder $q) => $q->whereNull('archived_at'))
            ->when($this->status === 'archived', fn (Builder $q) => $q->whereNotNull('archived_at'))
            ->when($this->status === 'low', function (Builder $q) {
                $q->whereNull('archived_at')->whereHas('variants', function (Builder $v) {
                    $v->whereNotNull('reorder_point')->whereRaw(
                        '(select coalesce(sum(quantity), 0) from stock_levels where stock_levels.product_variant_id = product_variants.id) <= product_variants.reorder_point'
                    );
                });
            });

        match ($this->sort) {
            'stock' => $query->orderBy('on_hand')->orderBy('name'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderBy('name'),
        };

        return $query->paginate(25);
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
