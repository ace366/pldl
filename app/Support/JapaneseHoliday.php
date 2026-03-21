<?php

namespace App\Support;

use Carbon\Carbon;

class JapaneseHoliday
{
    /**
     * 指定日が日本の祝日なら名称を返す。
     */
    public static function name(Carbon $date): ?string
    {
        $holidays = self::holidaysForYear((int)$date->year);

        return $holidays[$date->toDateString()] ?? null;
    }

    /**
     * @return array<string,string>
     */
    private static function holidaysForYear(int $year): array
    {
        static $cache = [];

        if (isset($cache[$year])) {
            return $cache[$year];
        }

        $holidays = [];

        self::addHoliday($holidays, Carbon::create($year, 1, 1), '元日');
        self::addHoliday($holidays, self::nthMonday($year, 1, 2), '成人の日');
        self::addHoliday($holidays, Carbon::create($year, 2, 11), '建国記念の日');
        self::addHoliday($holidays, Carbon::create($year, 2, 23), '天皇誕生日');
        self::addHoliday($holidays, Carbon::create($year, 3, self::springEquinoxDay($year)), '春分の日');
        self::addHoliday($holidays, Carbon::create($year, 4, 29), '昭和の日');
        self::addHoliday($holidays, Carbon::create($year, 5, 3), '憲法記念日');
        self::addHoliday($holidays, Carbon::create($year, 5, 4), 'みどりの日');
        self::addHoliday($holidays, Carbon::create($year, 5, 5), 'こどもの日');
        self::addHoliday($holidays, self::nthMonday($year, 7, 3), '海の日');
        self::addHoliday($holidays, Carbon::create($year, 8, 11), '山の日');
        self::addHoliday($holidays, self::nthMonday($year, 9, 3), '敬老の日');
        self::addHoliday($holidays, Carbon::create($year, 9, self::autumnEquinoxDay($year)), '秋分の日');
        self::addHoliday($holidays, self::nthMonday($year, 10, 2), 'スポーツの日');
        self::addHoliday($holidays, Carbon::create($year, 11, 3), '文化の日');
        self::addHoliday($holidays, Carbon::create($year, 11, 23), '勤労感謝の日');

        ksort($holidays);

        self::addCitizensHolidays($holidays, $year);
        self::addSubstituteHolidays($holidays, $year);

        ksort($holidays);

        return $cache[$year] = $holidays;
    }

    /**
     * @param array<string,string> $holidays
     */
    private static function addHoliday(array &$holidays, Carbon $date, string $name): void
    {
        $holidays[$date->toDateString()] = $name;
    }

    private static function nthMonday(int $year, int $month, int $nth): Carbon
    {
        $date = Carbon::create($year, $month, 1)->startOfMonth();
        while (!$date->isMonday()) {
            $date->addDay();
        }

        return $date->addWeeks($nth - 1);
    }

    /**
     * @param array<string,string> $holidays
     */
    private static function addCitizensHolidays(array &$holidays, int $year): void
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        for ($date = $start->copy()->addDay(); $date->lt($end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $ymd = $date->toDateString();
            if (isset($holidays[$ymd])) {
                continue;
            }

            $prev = $date->copy()->subDay()->toDateString();
            $next = $date->copy()->addDay()->toDateString();

            if (isset($holidays[$prev]) && isset($holidays[$next])) {
                $holidays[$ymd] = '国民の休日';
            }
        }
    }

    /**
     * @param array<string,string> $holidays
     */
    private static function addSubstituteHolidays(array &$holidays, int $year): void
    {
        $baseDates = array_keys($holidays);

        foreach ($baseDates as $dateString) {
            $holidayDate = Carbon::parse($dateString);
            if (!$holidayDate->isSunday()) {
                continue;
            }

            $substitute = $holidayDate->copy()->addDay();
            while (isset($holidays[$substitute->toDateString()])) {
                $substitute->addDay();
            }

            if ((int)$substitute->year === $year) {
                $holidays[$substitute->toDateString()] = '振替休日';
            }
        }
    }

    private static function springEquinoxDay(int $year): int
    {
        return (int)floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }

    private static function autumnEquinoxDay(int $year): int
    {
        return (int)floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
}
