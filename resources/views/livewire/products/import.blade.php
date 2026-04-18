<div>
    <x-page-header title="Import and export">
        <x-slot name="breadcrumb">
            <a href="{{ route('products.index') }}" wire:navigate class="hover:underline">Products</a>
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Import products from CSV" class="lg:col-span-2">
            <div class="mb-4 space-y-2 text-sm text-gray-600">
                <p>One row per variant. Rows are matched to existing products by <strong>SKU</strong>: known SKUs are updated, new SKUs are added to the product with the same name or create a new product.</p>
                <p>Categories and suppliers that don't exist yet are created. Stock quantities are not imported; receive or count stock afterwards so the ledger stays complete.</p>
                <p><a href="{{ route('products.template') }}" class="text-indigo-600 hover:underline">Download a template</a> or export your products below and edit that.</p>
            </div>

            <form wire:submit="import" class="space-y-4">
                <div>
                    <input type="file" wire:model="file" accept=".csv,text/csv" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                    <div wire:loading wire:target="file" class="mt-1 text-xs text-gray-500">Uploading…</div>
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="dryRun" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    Check the file without saving anything
                </label>

                <x-primary-button wire:loading.attr="disabled" wire:target="import,file">{{ $dryRun ? 'Check file' : 'Import' }}</x-primary-button>
            </form>

            @if ($result)
                <div class="mt-6 border-t border-gray-100 pt-4">
                    @if ($result['errors'] !== [])
                        <div class="rounded-md bg-rose-50 p-4">
                            <p class="text-sm font-medium text-rose-800">Nothing was imported. Fix these rows and try again:</p>
                            <ul class="mt-2 max-h-80 space-y-1 overflow-auto text-sm text-rose-700">
                                @foreach ($result['errors'] as $line => $messages)
                                    @foreach ($messages as $message)
                                        <li>@if ($line > 0)<span class="font-medium">Line {{ $line }}:</span>@endif {{ $message }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-800">
                            <p class="font-medium">{{ $result['dry_run'] ? 'The file looks good. Importing it will:' : 'Import finished:' }}</p>
                            <ul class="mt-2 list-disc space-y-0.5 ps-5">
                                <li>{{ $result['dry_run'] ? 'add' : 'added' }} {{ $result['created'] }} {{ Str::plural('variant', $result['created']) }} ({{ $result['products_created'] }} new {{ Str::plural('product', $result['products_created']) }})</li>
                                <li>{{ $result['dry_run'] ? 'update' : 'updated' }} {{ $result['updated'] }} existing {{ Str::plural('variant', $result['updated']) }}</li>
                                @if ($result['categories_created'] !== [])
                                    <li>{{ $result['dry_run'] ? 'create' : 'created' }} categories: {{ implode(', ', $result['categories_created']) }}</li>
                                @endif
                                @if ($result['suppliers_created'] !== [])
                                    <li>{{ $result['dry_run'] ? 'create' : 'created' }} suppliers: {{ implode(', ', $result['suppliers_created']) }}</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </x-card>

        <x-card title="Export">
            <p class="text-sm text-gray-600">Download every product variant with its price, reorder settings, stock on hand and average cost.</p>
            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('products.export') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">Export active products</a>
                <a href="{{ route('products.export', ['archived' => 1]) }}" class="text-center text-sm text-indigo-600 hover:underline">Include archived products</a>
            </div>
        </x-card>
    </div>
</div>
