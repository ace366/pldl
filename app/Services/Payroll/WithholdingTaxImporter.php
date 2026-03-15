<?php

namespace App\Services\Payroll;

use App\Models\WithholdingTaxTable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class WithholdingTaxImporter
{
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

            $min = max(0, (int)$minAmount);
            $max = max(0, (int)$maxAmount);
            $tax = max(0, (int)$taxAmount);
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
            return 0;
        }

        WithholdingTaxTable::query()->upsert(
            $rows,
            ['year', 'pay_type', 'column_type', 'dep_count', 'min_amount', 'max_amount'],
            ['tax_amount', 'updated_at']
        );

        return count($rows);
    }
}

