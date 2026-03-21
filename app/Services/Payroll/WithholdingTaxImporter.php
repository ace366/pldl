<?php

namespace App\Services\Payroll;

use App\Models\WithholdingTaxTable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class WithholdingTaxImporter
{
    private const UNSIGNED_INT_MAX = 4294967295;

    /**
     * @return int upsert対象行数
     */
    public function import(int $year, string $path): int
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('year must be between 2000 and 2100.');
        }
        if (!is_file($path)) {
            throw new InvalidArgumentException('CSV file not found: '.$path);
        }

        $rows = [];
        $invalidRows = [];
        $now = Carbon::now();

        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);

        foreach ($file as $index => $line) {
            if (!is_array($line)) {
                continue;
            }
            if (count($line) === 1 && ($line[0] === null || trim((string)$line[0]) === '')) {
                continue;
            }

            $cells = array_map(static fn ($v) => trim((string)$v), $line);
            if ($index === 0 && isset($cells[0]) && mb_strtolower($cells[0]) === 'pay_type') {
                continue;
            }
            if (count($cells) < 6) {
                continue;
            }

            [$payType, $columnType, $depCount, $minAmount, $maxAmount, $taxAmount] = array_slice($cells, 0, 6);
            if (!in_array($payType, ['monthly', 'daily'], true)) {
                continue;
            }
            if (!in_array($columnType, ['kou', 'otsu'], true)) {
                continue;
            }

            $dep = max(0, (int)$depCount);
            if ($columnType === 'otsu') {
                $dep = 0;
            }

            $min = $this->parseUnsignedInteger($minAmount);
            $max = $this->parseUnsignedInteger($maxAmount);
            $tax = $this->parseUnsignedInteger($taxAmount);
            if ($min === null || $max === null || $tax === null) {
                $invalidRows[] = $index + 1;
                continue;
            }
            if ($min > $max) {
                continue;
            }

            $rows[] = [
                'year' => $year,
                'pay_type' => $payType,
                'column_type' => $columnType,
                'dep_count' => $dep,
                'min_amount' => $min,
                'max_amount' => $max,
                'tax_amount' => $tax,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            if ($invalidRows !== []) {
                throw new RuntimeException('CSV内に保存できない金額が含まれます。該当行: '.implode(', ', $invalidRows));
            }
            return 0;
        }

        if ($invalidRows !== []) {
            throw new RuntimeException('CSV内に保存できない金額が含まれます。該当行: '.implode(', ', $invalidRows));
        }

        WithholdingTaxTable::query()->upsert(
            $rows,
            ['year', 'pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount'],
            ['tax_amount', 'updated_at']
        );

        return count($rows);
    }

    private function parseUnsignedInteger(string $value): ?int
    {
        $digits = preg_replace('/[^\d]/', '', trim($value)) ?? '';
        if ($digits === '') {
            return 0;
        }

        if (strlen($digits) > strlen((string)self::UNSIGNED_INT_MAX)) {
            return null;
        }
        if (
            strlen($digits) === strlen((string)self::UNSIGNED_INT_MAX)
            && strcmp($digits, (string)self::UNSIGNED_INT_MAX) > 0
        ) {
            return null;
        }

        return (int)$digits;
    }
}
