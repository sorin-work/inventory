<?php

use App\Support\Barcode;

test('accepts gtin codes with a correct check digit', function (string $code) {
    expect(Barcode::isValid($code))->toBeTrue();
})->with([
    'EAN-13' => '4006381333931',
    'EAN-13 with leading zero' => '0012345678905',
]);

test('rejects gtin codes with a wrong check digit', function (string $code) {
    expect(Barcode::isValid($code))->toBeFalse();
})->with(['4006381333932', '0012345678904']);

test('rejects codes with odd characters or the wrong length', function () {
    expect(Barcode::isValid('ab'))->toBeFalse()
        ->and(Barcode::isValid('has space'))->toBeFalse()
        ->and(Barcode::isValid(str_repeat('9', 49)))->toBeFalse();
});

test('normalizes empty values', function () {
    expect(Barcode::normalize('  '))->toBeNull()
        ->and(Barcode::normalize(null))->toBeNull();
});
