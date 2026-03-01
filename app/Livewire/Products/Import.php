<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithShop;
use App\Services\Csv\ProductImporter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Import and export products')]
class Import extends Component
{
    use WithFileUploads, WithShop;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public bool $dryRun = true;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function import(ProductImporter $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'file.mimes' => 'Upload a .csv file. In Excel or Numbers use “Save as” / “Export” and pick CSV.',
        ]);

        $this->result = $importer
            ->import($this->shop(), $this->file->getRealPath(), $this->dryRun)
            ->toArray();

        if (! $this->dryRun && $this->result['errors'] === []) {
            $this->notify("Imported {$this->result['created']} new and {$this->result['updated']} updated variants.");
            $this->reset('file');
        }
    }

    public function render()
    {
        return view('livewire.products.import');
    }
}
