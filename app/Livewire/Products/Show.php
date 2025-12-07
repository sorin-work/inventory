<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithShop;

    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    public function archive(): void
    {
        $this->authorizeOwner();

        $this->product->forceFill(['archived_at' => now()])->save();
        $this->notify('Product archived. It no longer shows up when picking stock.');
    }

    public function restore(): void
    {
        $this->authorizeOwner();

        $this->product->forceFill(['archived_at' => null])->save();
        $this->notify('Product restored.');
    }

    public function delete(): void
    {
        $this->authorizeOwner();

        $name = $this->product->name;
        $this->product->delete();

        session()->flash('status', "{$name} deleted.");
        $this->redirectRoute('products.index', navigate: true);
    }

    public function render()
    {
        $this->product->load(['variants', 'category', 'supplier']);

        return view('livewire.products.show')->title($this->product->name);
    }
}
