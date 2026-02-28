<?php

namespace App\Services\Csv;

final class ProductCsv
{
    /**
     * Columns in export order. Import accepts them in any order and ignores
     * the read-only ones (on_hand, average_cost, stock_value).
     */
    public const COLUMNS = [
        'product',
        'sku',
        'barcode',
        'option1_name',
        'option1_value',
        'option2_name',
        'option2_value',
        'option3_name',
        'option3_value',
        'category',
        'supplier',
        'unit',
        'price',
        'reorder_point',
        'reorder_quantity',
        'description',
        'on_hand',
        'average_cost',
    ];

    public const REQUIRED = ['product', 'sku'];

    public const MAX_OPTIONS = 3;
}
