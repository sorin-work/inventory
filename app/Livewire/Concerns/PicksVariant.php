<?php

namespace App\Livewire\Concerns;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Search box for picking a product variant by name, SKU or barcode. A
 * barcode scanner types the code and presses enter, so an exact SKU or
 * barcode match is selected straight away.
 */
trait PicksVariant
{
    public string $search = '';

    public ?int $variantId = null;

    public function mountPicksVariant(): void
    {
        $id = request()->integer('variant');

        if ($id > 0 && $this->activeVariants()->whereKey($id)->exists()) {
            $this->selectVariant($id);
        }
    }

    public function updatedSearch(): void
    {
        $match = $this->activeVariants()->whereCode($this->search)->first();

        if ($match !== null) {
            $this->selectVariant($match->id);
        }
    }

    public function pickFirstMatch(): void
    {
        $first = $this->variantMatches()->first();

        if ($first !== null) {
            $this->selectVariant($first->id);
        }
    }

    public function selectVariant(int $id): void
    {
        $variant = $this->activeVariants()->with('product')->findOrFail($id);

        $this->variantId = $variant->id;
        $this->search = '';
        $this->resetErrorBag('variantId');
        $this->variantSelected($variant);
    }

    public function clearVariant(): void
    {
        $this->variantId = null;
    }

    protected function variantSelected(ProductVariant $variant): void
    {
        //
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function variantMatches(): Collection
    {
        if (mb_strlen(trim($this->search)) < 2) {
            return collect();
        }

        return $this->activeVariants()
            ->search($this->search)
            ->with('product')
            ->limit(8)
            ->get();
    }

    public function selectedVariant(): ?ProductVariant
    {
        if ($this->variantId === null) {
            return null;
        }

        return ProductVariant::query()->with('product')->find($this->variantId);
    }

    /**
     * @return Builder<ProductVariant>
     */
    protected function activeVariants(): Builder
    {
        return ProductVariant::query()->whereHas('product', fn (Builder $q) => $q->whereNull('archived_at'));
    }
}
