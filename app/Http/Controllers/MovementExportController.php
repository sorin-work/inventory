<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Support\CsvCell;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovementExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string'],
            'location' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = StockMovement::query()
            ->with(['variant.product', 'location', 'user', 'purchaseOrder'])
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($validated['location'] ?? null, fn ($q, $id) => $q->where('location_id', $id))
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->latestFirst();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'type', 'sku', 'product', 'location', 'quantity', 'balance_after', 'unit_cost', 'unit_price', 'reason', 'reference', 'note', 'user'], escape: '');

            $query->chunk(500, function ($movements) use ($out) {
                foreach ($movements as $movement) {
                    fputcsv($out, array_map(CsvCell::escape(...), [
                        $movement->created_at->toDateTimeString(),
                        $movement->type->value,
                        $movement->variant->sku,
                        $movement->variant->displayName(),
                        $movement->location->name,
                        $movement->quantity,
                        $movement->balance_after,
                        $movement->unit_cost === null ? '' : Money::toDecimal($movement->unit_cost),
                        $movement->unit_price_cents === null ? '' : Money::toDecimal($movement->unit_price_cents),
                        $movement->reason?->label(),
                        $movement->purchaseOrder?->number,
                        $movement->note,
                        $movement->user?->name,
                    ]), escape: '');
                }
            });

            fclose($out);
        }, 'stock-movements-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
