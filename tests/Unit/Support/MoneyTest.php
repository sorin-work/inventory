<?php

use App\Support\Money;

test('formats cents with the currency symbol', function () {
    expect(Money::format(123456))->toBe('$1,234.56')
        ->and(Money::format(5, 'EUR'))->toBe('€0.05')
        ->and(Money::format(null))->toBe('$0.00');
});

test('formats fractional cents from average costs', function () {
    expect(Money::format(1234.5678))->toBe('$12.35');
});

test('parses user input into cents', function (string $input, int $cents) {
    expect(Money::parse($input))->toBe($cents);
})->with([
    ['12', 1200],
    ['12.5', 1250],
    ['12.05', 1205],
    ['0.99', 99],
    ['1,299.99', 129999],
    ['$4.20', 420],
    ['.5', 50],
    ['-3.10', -310],
]);

test('rejects input that is not an amount', function (string $input) {
    Money::parse($input);
})->with(['', 'abc', '1.234', '-', '.'])->throws(InvalidArgumentException::class);
