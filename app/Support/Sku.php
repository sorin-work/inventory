<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Sku
{
    public const PATTERN = '/^[A-Z0-9][A-Z0-9\-_.]{0,63}$/';

    public static function normalize(?string $sku): string
    {
        $sku = Str::upper(trim((string) $sku));

        return preg_replace('/\s+/', '-', $sku);
    }

    public static function isValid(string $sku): bool
    {
        return (bool) preg_match(self::PATTERN, $sku);
    }

    /**
     * Suggests a SKU from the product name and option values, e.g.
     * "Linen Shirt" + ["M", "Blue"] => "LINEN-SHIRT-M-BLUE".
     *
     * @param  list<string>  $options
     */
    public static function suggest(string $name, array $options = []): string
    {
        $parts = array_merge([$name], $options);
        $slug = Str::of(implode(' ', $parts))->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '-')->trim('-');

        return Str::limit((string) $slug, 64, '');
    }
}
