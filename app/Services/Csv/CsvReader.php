<?php

namespace App\Services\Csv;

use RuntimeException;

/**
 * Reads a CSV file into header-keyed rows. Handles the things spreadsheet
 * apps do to files: a UTF-8 byte order mark, Windows line endings and
 * blank lines.
 */
final class CsvReader
{
    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    public static function read(string $path): array
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('The file could not be opened.');
        }

        try {
            $first = fgets($handle);

            if ($first === false) {
                throw new RuntimeException('The file is empty.');
            }

            $first = self::stripBom($first);
            $delimiter = ',';
            $headers = array_map(self::normalizeHeader(...), str_getcsv(rtrim($first, "\r\n"), $delimiter, '"', ''));

            $rows = [];
            $line = 1;

            while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;

                if ($cells === [null] || implode('', $cells) === '') {
                    continue;
                }

                $row = [];

                foreach ($headers as $i => $header) {
                    if ($header !== '') {
                        $row[$header] = trim((string) ($cells[$i] ?? ''));
                    }
                }

                $rows[$line] = $row;
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    public static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim($header, '_');

        return $header;
    }

    private static function stripBom(string $line): string
    {
        return str_starts_with($line, "\xEF\xBB\xBF") ? substr($line, 3) : $line;
    }
}
