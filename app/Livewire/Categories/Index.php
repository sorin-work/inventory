<?php

namespace App\Livewire\Categories;

use App\Livewire\Concerns\WithShop;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categories')]
class Index extends Component
{
    use WithShop;

    public string $name = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public function create(): void
    {
        $this->name = trim($this->name);

        $this->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueName()],
        ]);

        Category::create(['name' => $this->name]);

        $this->notify("Category {$this->name} added.");
        $this->reset('name');
    }

    public function edit(int $id): void
    {
        $category = Category::query()->findOrFail($id);

        $this->editingId = $category->id;
        $this->editingName = $category->name;
    }

    public function update(): void
    {
        $this->editingName = trim($this->editingName);

        $this->validate([
            'editingName' => ['required', 'string', 'max:100', $this->uniqueName($this->editingId)],
        ], [], ['editingName' => 'name']);

        Category::query()->findOrFail($this->editingId)->update(['name' => $this->editingName]);

        $this->reset('editingId', 'editingName');
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingName');
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        $this->authorizeOwner();

        $category = Category::query()->findOrFail($id);
        $category->delete();

        $this->notify("{$category->name} deleted. Its products are now uncategorised.");
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()->withCount('products')->orderBy('name')->get();
    }

    private function uniqueName(?int $ignore = null): Unique
    {
        return Rule::unique('categories', 'name')->where('shop_id', $this->shop()->id)->ignore($ignore);
    }

    public function render()
    {
        return view('livewire.categories.index');
    }
}
