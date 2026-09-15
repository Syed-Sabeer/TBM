<?php

namespace App\Services\Imports;

use Illuminate\Support\Str;

/**
 * Reads a delimited file into a header row and an array of rows.
 *
 * CSV and tab-separated only, on purpose: no spreadsheet library is required
 * to run the application, and the mill sends CSV. An .xlsx dropped on the
 * uploader is rejected with a message telling the user to save it as CSV,
 * which is a better failure than a half-parsed binary.
 */
class SheetReader
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function read(int $limit = 5000): array
    {
        if (! is_readable($this->path)) {
            throw new ImportException('That file could not be opened.');
        }

        $delimiter = $this->detectDelimiter();
        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new ImportException('That file could not be opened.');
        }

        $headers = [];
        $rows = [];

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Skip the blank lines mills leave above the real header.
                if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                if ($headers === []) {
                    $headers = array_map(fn ($h) => trim((string) $h), $row);

                    continue;
                }

                $rows[] = array_map(fn ($v) => trim((string) $v), $row);

                if (count($rows) >= $limit) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        if ($headers === []) {
            throw new ImportException('That file has no header row.');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * A first guess at which source column feeds which domain field, so the
     * mapping screen opens mostly filled in. Every guess is still shown to the
     * user before anything is applied.
     */
    public function suggestMapping(array $headers): array
    {
        $patterns = [
            'parent_sku' => ['mill', 'parent', 'item', 'style', 'ref', 'sku', 'code'],
            'description' => ['desc', 'name', 'product'],
            'shade' => ['colour', 'color', 'shade'],
            'warehouse_code' => ['wh', 'warehouse', 'location', 'site', 'depot'],
            'quantity' => ['qty', 'quantity', 'lot', 'stock', 'available', 'on hand'],
            'cost' => ['cost', 'fob', 'price', 'usd'],
            'ready_on' => ['ready', 'eta', 'date', 'available on'],
        ];

        $map = [];
        $taken = [];

        foreach ($patterns as $field => $needles) {
            foreach ($headers as $index => $header) {
                if (in_array($index, $taken, true)) {
                    continue;
                }

                $normalised = Str::lower(preg_replace('/[^a-z0-9 ]/i', ' ', $header));

                foreach ($needles as $needle) {
                    if (str_contains($normalised, $needle)) {
                        $map[$field] = $index;
                        $taken[] = $index;

                        continue 3;
                    }
                }
            }
        }

        return $map;
    }

    private function detectDelimiter(): string
    {
        $sample = (string) file_get_contents($this->path, false, null, 0, 4096);

        return substr_count($sample, "\t") > substr_count($sample, ',') ? "\t" : ',';
    }
}
