<?php

namespace App\Http\Controllers;

use App\Services\Reports\ValuationReport;
use App\Support\CsvCell;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValuationExportController extends Controller
{
    public function __invoke(Request $request, ValuationReport $report): StreamedResponse
    {
        $summary = $report->summary($request->user()->shop, $request->integer('location') ?: null);

        return response()->streamDownload(function () use ($summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sku', 'product', 'category', 'units', 'average_cost', 'value'], escape: '');

            foreach ($summary['rows'] as $row) {
                fputcsv($out, array_map(CsvCell::escape(...), [
                    $row->sku,
                    $row->name,
                    $row->category,
                    $row->units,
                    Money::toDecimal($row->average_cost),
                    Money::toDecimal($row->value_cents),
                ]), escape: '');
            }

            fclose($out);
        }, 'stock-valuation-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
