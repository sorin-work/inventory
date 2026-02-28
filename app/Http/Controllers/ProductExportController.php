<?php

namespace App\Http\Controllers;

use App\Services\Csv\ProductExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportController extends Controller
{
    public function __invoke(Request $request, ProductExporter $exporter): StreamedResponse
    {
        return $exporter->download($request->user()->shop);
    }
}
