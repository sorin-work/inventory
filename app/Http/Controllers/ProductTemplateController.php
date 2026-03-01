<?php

namespace App\Http\Controllers;

use App\Services\Csv\ProductCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            $columns = array_values(array_diff(ProductCsv::COLUMNS, ['on_hand', 'average_cost']));
            fputcsv($out, $columns, escape: '');
            fputcsv($out, [
                'Linen shirt', 'LINEN-SHIRT-M-BLUE', '', 'Size', 'M', 'Colour', 'Blue', '', '',
                'Clothing', 'Northwind Textiles', 'each', '39.00', '5', '12', 'Relaxed fit, 100% linen',
            ], escape: '');
            fclose($out);
        }, 'products-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
