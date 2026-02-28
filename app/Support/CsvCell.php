<?php

namespace App\Support;

final class CsvCell
{
    /**
     * Spreadsheet apps run cells that start with these as formulas, which
     * turns an exported product name into a way to run code on whoever opens
     * the file. Prefixing a quote keeps the text as text.
     */
    public static function escape(string|int|float|null $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Undo escape() when the same file is imported again.
     */
    public static function unescape(?string $value): string
    {
        $value = trim((string) $value);

        if (strlen($value) > 1 && $value[0] === "'" && in_array($value[1], ['=', '+', '-', '@'], true)) {
            return substr($value, 1);
        }

        return $value;
    }
}
