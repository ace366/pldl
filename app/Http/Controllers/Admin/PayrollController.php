<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPayment;
use App\Models\ShiftAttendance;
use App\Models\User;
use App\Models\WithholdingTaxTable;
use App\Services\PayrollCalculator;
use App\Services\Payroll\WithholdingTaxCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    private const DEFAULT_PAY_TYPE = 'monthly';

    public function index(Request $request)
    {
        $month = $this->normalizeMonth((string)$request->query('month', Carbon::now()->format('Y-m')));
        $q = trim((string)$request->query('q', ''));

        $all = ShiftAttendance::query()
            ->forMonth($month)
            ->with(['user'])
            ->orderBy('user_id')
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        $payments = PayrollPayment::query()
            ->where('year_month', $month)
            ->get()
            ->keyBy('user_id');

        $grouped = $all->groupBy('user_id');
        $rows = [];

        foreach ($grouped as $userId => $items) {
            /** @var ShiftAttendance $first */
            $first = $items->first();
            $user = $first?->user ?: User::query()->find((int)$userId);
            if (!$user) {
                continue;
            }

            $name = trim((string)($user->last_name ?? '').' '.(string)($user->first_name ?? ''));
            $displayName = $name !== '' ? $name : ((string)$user->name ?: 'ユーザー#'.$user->id);

            if ($q !== '') {
                $haystacks = [
                    mb_strtolower($displayName),
                    mb_strtolower((string)$user->email),
                    mb_strtolower((string)($user->last_name_kana ?? '')),
                    mb_strtolower((string)($user->first_name_kana ?? '')),
                ];
                $needle = mb_strtolower($q);
                $matched = false;
                foreach ($haystacks as $h) {
                    if ($h !== '' && mb_strpos($h, $needle) !== false) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            $hourlyWage = (int)($user->hourly_wage ?? 0);
            $totals = PayrollCalculator::totals($items, $hourlyWage);
            $workedDays = $items->filter(fn ($a) => (int)($a->work_minutes ?? 0) > 0)->count();
            $payment = $payments->get((int)$user->id);

            $rows[] = [
                'user' => $user,
                'display_name' => $displayName,
                'worked_days' => $workedDays,
                'attendance_count' => $items->count(),
                'hourly_wage' => $hourlyWage,
                'totals' => $totals,
                'payment' => $payment,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            return strcmp((string)$a['display_name'], (string)$b['display_name']);
        });

        return view('admin.payroll.index', [
            'month' => $month,
            'q' => $q,
            'rows' => $rows,
            'period' => PayrollCalculator::monthPeriod($month),
        ]);
    }

    public function show(Request $request, User $user)
    {
        $month = $this->normalizeMonth((string)$request->query('month', Carbon::now()->format('Y-m')));
        $schedule = $this->buildPaySchedule($month);

        $items = ShiftAttendance::query()
            ->where('user_id', $user->id)
            ->forMonth($month)
            ->with(['shift', 'base'])
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        $hourlyWage = (int)($user->hourly_wage ?? 0);
        $rows = [];
        foreach ($items as $item) {
            $rows[(int)$item->id] = PayrollCalculator::row($item, $hourlyWage);
        }
        $totals = PayrollCalculator::totals($items, $hourlyWage);
        $grossPay = (int)($totals['total_gross_pay_yen'] ?? 0);

        $payment = PayrollPayment::query()
            ->where('user_id', $user->id)
            ->where('year_month', $month)
            ->first();

        $old = $request->session()->getOldInput();
        $form = [
            'pay_type' => (string)($old['pay_type'] ?? $payment?->pay_type ?? self::DEFAULT_PAY_TYPE),
            'tax_year' => (int)($old['tax_year'] ?? $payment?->tax_year ?? (int)$schedule['payment_date']->format('Y')),
            'column_type' => (string)($old['column_type'] ?? $payment?->column_type ?? 'kou'),
            'dep_count' => (int)($old['dep_count'] ?? $payment?->dep_count ?? 0),
            'social_insurance_amount' => (int)($old['social_insurance_amount'] ?? $payment?->social_insurance_amount ?? 0),
        ];
        if ($form['column_type'] === 'otsu') {
            $form['dep_count'] = 0;
        }

        $calculator = app(WithholdingTaxCalculator::class);
        $withholdingTax = $calculator->calc(
            $form['tax_year'],
            $grossPay,
            $form['social_insurance_amount'],
            $form['column_type'] === 'kou',
            $form['dep_count'],
            $form['pay_type']
        );
        $taxableAmount = max(0, $grossPay - $form['social_insurance_amount']);
        $netPay = max(0, $grossPay - $form['social_insurance_amount'] - $withholdingTax);
        $hasTaxRow = $this->hasTaxTableRow(
            $form['tax_year'],
            $form['pay_type'],
            $form['column_type'],
            $form['dep_count'],
            $taxableAmount
        );

        $name = trim((string)($user->last_name ?? '').' '.(string)($user->first_name ?? ''));
        $displayName = $name !== '' ? $name : ((string)$user->name ?: 'ユーザー#'.$user->id);

        return view('admin.payroll.statement', [
            'month' => $month,
            'user' => $user,
            'displayName' => $displayName,
            'items' => $items,
            'rows' => $rows,
            'totals' => $totals,
            'period' => PayrollCalculator::monthPeriod($month),
            'payment' => $payment,
            'form' => $form,
            'paySchedule' => [
                'closing_date' => $schedule['closing_date']->toDateString(),
                'payment_date' => $schedule['payment_date']->toDateString(),
            ],
            'withholding' => [
                'gross_pay' => $grossPay,
                'taxable_amount' => $taxableAmount,
                'withholding_tax' => $withholdingTax,
                'social_insurance_amount' => $form['social_insurance_amount'],
                'net_pay' => $netPay,
                'has_tax_row' => $hasTaxRow,
            ],
            'company' => [
                'name' => 'NPO法人 Playful Learning Design Lab.',
                'zipcode' => '379-2313',
                'address' => '群馬県みどり市笠懸町鹿3616-1',
                'representative' => '松島咲季子',
            ],
            'autoPrint' => (bool)$request->boolean('print'),
        ]);
    }

    public function savePayment(Request $request, User $user)
    {
        $month = $this->normalizeMonth((string)$request->input('month', Carbon::now()->format('Y-m')));
        $schedule = $this->buildPaySchedule($month);

        $validated = $request->validate([
            'pay_type' => ['required', Rule::in(['monthly', 'daily'])],
            'tax_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'column_type' => ['required', Rule::in(['kou', 'otsu'])],
            'dep_count' => [
                Rule::requiredIf(fn () => (string)$request->input('column_type') === 'kou'),
                'nullable',
                'integer',
                'min:0',
                'max:15',
            ],
            'social_insurance_amount' => ['required', 'integer', 'min:0', 'max:99999999'],
        ], [
            'dep_count.required' => '甲欄を選択した場合、扶養人数は必須です。',
        ]);

        $items = ShiftAttendance::query()
            ->where('user_id', $user->id)
            ->forMonth($month)
            ->get();
        $hourlyWage = (int)($user->hourly_wage ?? 0);
        $totals = PayrollCalculator::totals($items, $hourlyWage);
        $grossPay = (int)($totals['total_gross_pay_yen'] ?? 0);

        $columnType = (string)$validated['column_type'];
        $depCount = $columnType === 'kou'
            ? (int)($validated['dep_count'] ?? 0)
            : 0;
        $socialInsuranceAmount = (int)$validated['social_insurance_amount'];

        $calculator = app(WithholdingTaxCalculator::class);
        $withholdingTax = $calculator->calc(
            (int)$validated['tax_year'],
            $grossPay,
            $socialInsuranceAmount,
            $columnType === 'kou',
            $depCount,
            (string)$validated['pay_type']
        );
        $taxableAmount = max(0, $grossPay - $socialInsuranceAmount);
        $netPay = max(0, $grossPay - $socialInsuranceAmount - $withholdingTax);

        PayrollPayment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'year_month' => $month,
            ],
            [
                'tax_year' => (int)$validated['tax_year'],
                'pay_type' => (string)$validated['pay_type'],
                'column_type' => $columnType,
                'dep_count' => $depCount,
                'social_insurance_amount' => $socialInsuranceAmount,
                'gross_pay' => $grossPay,
                'taxable_amount' => $taxableAmount,
                'withholding_tax' => $withholdingTax,
                'net_pay' => $netPay,
                'closing_date' => $schedule['closing_date']->toDateString(),
                'payment_date' => $schedule['payment_date']->toDateString(),
                'confirmed_at' => now(),
            ]
        );

        return redirect()
            ->route('admin.payroll.show', ['user' => $user->id, 'month' => $month])
            ->with('success', '給与控除情報を保存しました。');
    }

    private function normalizeMonth(string $month): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return Carbon::now()->format('Y-m');
        }
        try {
            return Carbon::createFromFormat('Y-m', $month)->format('Y-m');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m');
        }
    }

    /**
     * @return array{closing_date:Carbon,payment_date:Carbon}
     */
    private function buildPaySchedule(string $month): array
    {
        $base = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [
            'closing_date' => $base->copy()->endOfMonth(),
            'payment_date' => $base->copy()->addMonthNoOverflow()->day(25),
        ];
    }

    private function hasTaxTableRow(
        int $year,
        string $payType,
        string $columnType,
        int $depCount,
        int $taxableAmount
    ): bool {
        $dep = $columnType === 'otsu' ? 0 : max(0, $depCount);

        return WithholdingTaxTable::query()
            ->where('year', $year)
            ->where('pay_type', $payType)
            ->where('column_type', $columnType)
            ->where('dep_count', $dep)
            ->where('min_amount', '<=', $taxableAmount)
            ->where('max_amount', '>=', $taxableAmount)
            ->exists();
    }
}
