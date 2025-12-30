<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Inventory') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-gray-900">
        <div class="mx-auto flex min-h-full max-w-5xl flex-col px-6">
            <header class="flex items-center justify-between py-6">
                <div class="flex items-center gap-2">
                    <x-application-logo class="h-8 w-8 fill-current text-indigo-600" />
                    <span class="font-semibold">{{ config('app.name', 'Inventory') }}</span>
                </div>
                <nav class="flex gap-4 text-sm">
                    <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-gray-900">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-500">Create a shop</a>
                </nav>
            </header>

            <main class="flex flex-1 flex-col justify-center py-16">
                <h1 class="max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl">Know what's on your shelves.</h1>
                <p class="mt-6 max-w-xl text-lg text-gray-600">
                    Stock tracking for small shops: products and variants with SKUs and barcodes,
                    several stock locations, a full movement history, purchase orders that book
                    in deliveries, low stock alerts and what it's all worth at average cost.
                </p>
                <div class="mt-8 flex gap-3">
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Get started</a>
                    <a href="{{ route('login') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Log in</a>
                </div>

                <dl class="mt-16 grid gap-8 sm:grid-cols-3">
                    <div>
                        <dt class="font-semibold">Every movement on record</dt>
                        <dd class="mt-1 text-sm text-gray-600">Receipts, sales, adjustments with a reason and transfers between locations, with a running balance per product.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Reorder before you run out</dt>
                        <dd class="mt-1 text-sm text-gray-600">Reorder points per variant and a low stock report that turns into draft purchase orders per supplier.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Spreadsheet friendly</dt>
                        <dd class="mt-1 text-sm text-gray-600">Import and export products as CSV, including the files Excel saves with semicolons.</dd>
                    </div>
                </dl>
            </main>

            <footer class="py-6 text-xs text-gray-400">MIT licensed.</footer>
        </div>
    </body>
</html>
