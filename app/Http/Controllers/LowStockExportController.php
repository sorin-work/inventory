<?php

namespace App\Http\Controllers;

use App\Services\Reports\LowStockReport;
use App\Support\CsvCell;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LowStockExportController extends Controller
{
    public function __invoke(Request $request, LowStockReport $report): StreamedResponse
    {
        $rows = $report->rows(
            $request->user()->shop,
            $request->integer('category') ?: null,
            $request->integer('supplier') ?: null,
        );

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sku', 'product', 'supplier', 'on_hand', 'reorder_point', 'on_order', 'suggested'], escape: '');

            foreach ($rows as $row) {
                fputcsv($out, array_map(CsvCell::escape(...), [
                    $row->variant->sku,
                    $row->variant->displayName(),
                    $row->variant->product->supplier?->name,
                    $row->onHand,
                    $row->variant->reorder_point,
                    $row->onOrder,
                    $row->suggestedQuantity(),
                ]), escape: '');
            }

            fclose($out);
        }, 'low-stock-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
