<?php

namespace App\Services\Payroll;

use App\Models\WithholdingTaxTable;

class WithholdingTaxCalculator
{
    public function calc(
        int $year,
        int $grossPay,
        int $socialInsurance,
        bool $isKou,
        int $depCount,
        string $payType = 'monthly'
    ): int {
        $taxable = max(0, $grossPay - $socialInsurance);
        $columnType = $isKou ? 'kou' : 'otsu';
        $effectiveDepCount = $isKou ? max(0, $depCount) : 0;
        $effectivePayType = in_array($payType, ['monthly', 'daily'], true) ? $payType : 'monthly';

        $row = WithholdingTaxTable::query()
            ->where('year', $year)
            ->where('pay_type', $effectivePayType)
            ->where('column_type', $columnType)
            ->where('dep_count', $effectiveDepCount)
            ->where('min_amount', '<=', $taxable)
            ->where('max_amount', '>=', $taxable)
            ->orderBy('min_amount')
            ->first();

        return (int)($row?->tax_amount ?? 0);
    }
}

