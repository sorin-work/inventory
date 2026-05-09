<?php

namespace App\Support;

/**
 * LIKE patterns that treat % and _ in what someone typed as plain text.
 * "!" is used as the escape character because MySQL and SQLite both
 * accept it in an ESCAPE clause, unlike a backslash.
 */
final class Like
{
    public static function contains(string $term): string
    {
        return '%'.self::escape(trim($term)).'%';
    }

    public static function escape(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }
}
