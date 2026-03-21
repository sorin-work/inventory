<?php

namespace App\Livewire\Locations;

use App\Livewire\Concerns\WithShop;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Support\AverageCost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Locations')]
class Index extends Component
{
    use WithShop;

    public string $name = '';

    public string $code = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public string $editingCode = '';

    public function create(): void
    {
        $this->name = trim($this->name);
        $this->code = strtoupper(trim($this->code));

        $this->validate([
            'name' => ['required', 'string', 'max:100', $this->uniqueName()],
            'code' => ['nullable', 'string', 'max:20', 'alpha_dash'],
        ]);

        $location = Location::create([
            'name' => $this->name,
            'code' => $this->code ?: null,
            'is_default' => ! Location::query()->exists(),
        ]);

        $this->notify("{$location->name} added.");
        $this->reset('name', 'code');
    }

    public function edit(int $id): void
    {
        $location = Location::query()->findOrFail($id);

        $this->editingId = $location->id;
        $this->editingName = $location->name;
        $this->editingCode = (string) $location->code;
    }

    public function update(): void
    {
        $this->editingName = trim($this->editingName);
        $this->editingCode = strtoupper(trim($this->editingCode));

        $this->validate([
            'editingName' => ['required', 'string', 'max:100', $this->uniqueName($this->editingId)],
            'editingCode' => ['nullable', 'string', 'max:20', 'alpha_dash'],
        ], [], ['editingName' => 'name', 'editingCode' => 'code']);

        Location::query()->findOrFail($this->editingId)->update([
            'name' => $this->editingName,
            'code' => $this->editingCode ?: null,
        ]);

        $this->reset('editingId', 'editingName', 'editingCode');
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingName', 'editingCode');
        $this->resetErrorBag();
    }

    public function makeDefault(int $id): void
    {
        $location = Location::query()->findOrFail($id);
        $location->makeDefault();

        $this->notify("{$location->name} is now the default location.");
    }

    public function delete(int $id): void
    {
        $this->authorizeOwner();

        $location = Location::query()->findOrFail($id);

        if ($location->movements()->exists() || PurchaseOrder::query()->where('location_id', $id)->exists()) {
            $this->dispatch('notify', message: "{$location->name} has stock history and can't be deleted.");

            return;
        }

        if (Location::query()->count() === 1) {
            $this->dispatch('notify', message: 'A shop needs at least one location.');

            return;
        }

        $wasDefault = $location->is_default;
        $location->delete();

        if ($wasDefault) {
            Location::query()->orderBy('id')->first()?->makeDefault();
        }

        $this->notify("{$location->name} deleted.");
    }

    /**
     * @return Collection<int, Location>
     */
    public function locations(): Collection
    {
        $totals = DB::table('stock_levels')
            ->join('product_variants', 'product_variants.id', '=', 'stock_levels.product_variant_id')
            ->where('product_variants.shop_id', $this->shop()->id)
            ->where('stock_levels.quantity', '>', 0)
            ->groupBy('stock_levels.location_id')
            ->selectRaw('stock_levels.location_id, SUM(stock_levels.quantity) as units, SUM(stock_levels.quantity * product_variants.average_cost) as value')
            ->get()
            ->keyBy('location_id');

        return Location::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->each(function (Location $location) use ($totals) {
                $location->setAttribute('units', (int) ($totals[$location->id]->units ?? 0));
                $location->setAttribute('value_cents', AverageCost::value(1, (float) ($totals[$location->id]->value ?? 0)));
            });
    }

    private function uniqueName(?int $ignore = null): Unique
    {
        return Rule::unique('locations', 'name')->where('shop_id', $this->shop()->id)->ignore($ignore);
    }

    public function render()
    {
        return view('livewire.locations.index');
    }
}
