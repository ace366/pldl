<?php

namespace App\Services;

use App\Models\ShiftAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculator
{
    public const ROUND_UNIT_MINUTES = 15;

    public static function roundDownMinutes(int $minutes, int $unit = self::ROUND_UNIT_MINUTES): int
    {
        if ($minutes <= 0 || $unit <= 0) {
            return 0;
        }

        return intdiv($minutes, $unit) * $unit;
    }

    public static function minutesToLabel(int $minutes): string
    {
        $m = max(0, $minutes);
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    /**
     * @return array{
     *  work_minutes:int,
     *  auto_break_minutes:int,
     *  extra_break_minutes:int,
     *  payable_minutes_raw:int,
     *  payable_minutes:int,
     *  payable_label:string,
     *  gross_pay_yen:int,
     *  hourly_wage:int
     * }
     */
    public static function row(ShiftAttendance $attendance, ?int $hourlyWage): array
    {
        $workMinutes = max(0, (int)($attendance->work_minutes ?? 0));
        $autoBreak = max(0, (int)($attendance->auto_break_minutes ?? 0));
        $extraBreak = max(0, (int)($attendance->break_minutes ?? 0));

        $payableRaw = max(0, $workMinutes - $extraBreak);
        $payableMinutes = self::roundDownMinutes($payableRaw, self::ROUND_UNIT_MINUTES);

        $wage = max(0, (int)($hourlyWage ?? 0));
        $grossPay = (int)floor($wage * ($payableMinutes / 60));

        return [
            'work_minutes' => $workMinutes,
            'auto_break_minutes' => $autoBreak,
            'extra_break_minutes' => $extraBreak,
            'payable_minutes_raw' => $payableRaw,
            'payable_minutes' => $payableMinutes,
            'payable_label' => self::minutesToLabel($payableMinutes),
            'gross_pay_yen' => $grossPay,
            'hourly_wage' => $wage,
        ];
    }

    /**
     * @param Collection<int,ShiftAttendance> $items
     * @return array{
     *  total_work_minutes:int,
     *  total_auto_break_minutes:int,
     *  total_extra_break_minutes:int,
     *  total_payable_minutes_raw:int,
     *  total_payable_minutes:int,
     *  total_payable_label:string,
     *  total_gross_pay_yen:int
     * }
     */
    public static function totals(Collection $items, ?int $hourlyWage): array
    {
        $totalWork = 0;
        $totalAutoBreak = 0;
        $totalExtraBreak = 0;
        $totalPayableRaw = 0;
        $totalPayable = 0;
        $totalGross = 0;

        foreach ($items as $attendance) {
            $row = self::row($attendance, $hourlyWage);
            $totalWork += $row['work_minutes'];
            $totalAutoBreak += $row['auto_break_minutes'];
            $totalExtraBreak += $row['extra_break_minutes'];
            $totalPayableRaw += $row['payable_minutes_raw'];
            $totalPayable += $row['payable_minutes'];
            $totalGross += $row['gross_pay_yen'];
        }

        return [
            'total_work_minutes' => $totalWork,
            'total_auto_break_minutes' => $totalAutoBreak,
            'total_extra_break_minutes' => $totalExtraBreak,
            'total_payable_minutes_raw' => $totalPayableRaw,
            'total_payable_minutes' => $totalPayable,
            'total_payable_label' => self::minutesToLabel($totalPayable),
            'total_gross_pay_yen' => $totalGross,
        ];
    }

    /**
     * @return array{period_start:string,period_end:string}
     */
    public static function monthPeriod(string $month): array
    {
        $base = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        return [
            'period_start' => $base->copy()->startOfMonth()->toDateString(),
            'period_end' => $base->copy()->endOfMonth()->toDateString(),
        ];
    }
}

