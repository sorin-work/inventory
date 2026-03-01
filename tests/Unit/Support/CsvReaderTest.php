<?php

use App\Services\Csv\CsvReader;

function tempCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $contents);

    return $path;
}

test('reads rows keyed by normalized header', function () {
    $csv = CsvReader::read(tempCsv("Product,SKU,Reorder Quantity\nMug,MUG-1,6\n"));

    expect($csv['headers'])->toBe(['product', 'sku', 'reorder_quantity'])
        ->and($csv['rows'])->toBe([2 => ['product' => 'Mug', 'sku' => 'MUG-1', 'reorder_quantity' => '6']]);
});

test('strips a byte order mark and skips blank lines', function () {
    $csv = CsvReader::read(tempCsv("\xEF\xBB\xBFproduct,sku\r\n\r\nMug,MUG-1\r\n,\r\nBowl,BOWL-1\r\n"));

    expect($csv['headers'][0])->toBe('product')
        ->and(array_keys($csv['rows']))->toBe([3, 5]);
});

test('refuses empty files', function () {
    CsvReader::read(tempCsv(''));
})->throws(RuntimeException::class, 'empty');
