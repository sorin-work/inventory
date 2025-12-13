<?php

namespace App\Support;

/**
 * Barcodes are mostly EAN-13 / UPC-A from suppliers, but shops also print
 * their own Code 128 labels, so anything that isn't a GTIN length is only
 * checked for sensible characters.
 */
final class Barcode
{
    private const GTIN_LENGTHS = [8, 12, 13, 14];

    public static function normalize(?string $barcode): ?string
    {
        if ($barcode === null) {
            return null;
        }

        $barcode = trim($barcode);

        return $barcode === '' ? null : $barcode;
    }

    public static function isValid(string $barcode): bool
    {
        if (ctype_digit($barcode) && in_array(strlen($barcode), self::GTIN_LENGTHS, true)) {
            return self::hasValidCheckDigit($barcode);
        }

        return (bool) preg_match('/^[A-Za-z0-9\-.\/]{3,48}$/', $barcode);
    }

    /**
     * GTIN check digit: digits are weighted 1, 3, 1, 3... and the check
     * digit brings the sum up to a multiple of ten.
     */
    public static function hasValidCheckDigit(string $digits): bool
    {
        $body = substr($digits, 0, -1);
        $check = (int) substr($digits, -1);
        $sum = 0;

        for ($i = 0; $i < strlen($body); $i++) {
            $sum += (int) $body[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10 === $check;
    }
}
