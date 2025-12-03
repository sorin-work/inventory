<?php

use App\Support\Sku;

test('normalizes to upper case without spaces', function () {
    expect(Sku::normalize('  linen shirt m '))->toBe('LINEN-SHIRT-M')
        ->and(Sku::normalize(null))->toBe('');
});

test('suggests a sku from the name and option values', function () {
    expect(Sku::suggest('Linen Shirt', ['M', 'Blue']))->toBe('LINEN-SHIRT-M-BLUE')
        ->and(Sku::suggest('Café crème mug'))->toBe('CAFE-CREME-MUG')
        ->and(Sku::suggest('Soy candle, cedar & sage'))->toBe('SOY-CANDLE-CEDAR-SAGE');
});
