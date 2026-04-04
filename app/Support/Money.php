<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money is stored as integer cents. Average costs keep four decimals of a
 * cent, so formatting accepts floats too.
 */
final class Money
{
    private const SYMBOLS = [
        'USD' => '$',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'NZD' => 'NZ$',
        'EUR' => '€',
        'GBP' => '£',
        'RON' => 'lei ',
        'CHF' => 'CHF ',
        'SEK' => 'kr ',
        'JPY' => '¥',
    ];

    /**
     * @return array<string, string>
     */
    public static function currencies(): array
    {
        return [
            'USD' => 'US dollar',
            'CAD' => 'Canadian dollar',
            'AUD' => 'Australian dollar',
            'NZD' => 'New Zealand dollar',
            'EUR' => 'Euro',
            'GBP' => 'Pound sterling',
            'RON' => 'Romanian leu',
            'CHF' => 'Swiss franc',
            'SEK' => 'Swedish krona',
            'JPY' => 'Japanese yen',
        ];
    }

    public static function format(int|float|null $cents, string $currency = 'USD'): string
    {
        $cents ??= 0;
        $symbol = self::SYMBOLS[$currency] ?? $currency.' ';

        return $symbol.number_format($cents / 100, 2, '.', ',');
    }

    /**
     * Turns user input such as "12", "12.5", "1,299.99" or "$4.20" into cents.
     * A comma followed by one or two digits at the end is a decimal comma, as
     * written in most of Europe ("12,50", "1.299,99").
     */
    public static function parse(string|int|float|null $input): int
    {
        if ($input === null || $input === '') {
            throw new InvalidArgumentException('An amount is required.');
        }

        if (is_int($input)) {
            return $input * 100;
        }

        $clean = preg_replace('/[^0-9.,\-]/', '', (string) $input);

        if (preg_match('/,\d{1,2}$/', $clean)) {
            $clean = str_replace(['.', ','], ['', '.'], $clean);
        }

        $clean = str_replace(',', '', $clean);

        if ($clean === '' || ! preg_match('/^-?\d*(\.\d{0,2})?$/', $clean) || $clean === '-' || $clean === '.') {
            throw new InvalidArgumentException("\"{$input}\" is not a valid amount.");
        }

        [$whole, $fraction] = array_pad(explode('.', ltrim($clean, '-')), 2, '');
        $cents = ((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');

        return str_starts_with($clean, '-') ? -$cents : $cents;
    }

    /**
     * Cents as a plain decimal string for form inputs and CSV files.
     */
    public static function toDecimal(int|float|null $cents): string
    {
        return number_format(($cents ?? 0) / 100, 2, '.', '');
    }
}
