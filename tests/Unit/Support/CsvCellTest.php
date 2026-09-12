<?php

use App\Support\CsvCell;

test('prefixes cells that a spreadsheet would run as formulas', function (string $value) {
    expect(CsvCell::escape($value))->toBe("'".$value);
})->with(['=SUM(A1:A9)', '+cmd', '-2+3', '@import']);

test('leaves ordinary values and negative numbers alone', function () {
    expect(CsvCell::escape('Linen shirt'))->toBe('Linen shirt')
        ->and(CsvCell::escape('-5'))->toBe('-5')
        ->and(CsvCell::escape(12))->toBe('12')
        ->and(CsvCell::escape(null))->toBe('');
});

test('unescape reverses escape', function () {
    expect(CsvCell::unescape(CsvCell::escape('=HYPERLINK("x")')))->toBe('=HYPERLINK("x")')
        ->and(CsvCell::unescape("'quoted"))->toBe("'quoted")
        ->and(CsvCell::unescape('  padded '))->toBe('padded');
});
