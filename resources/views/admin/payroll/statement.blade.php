@php
    $month = $month ?? now()->format('Y-m');
    $items = $items ?? collect();
    $rows = $rows ?? [];
    $totals = $totals ?? [
        'total_work_minutes' => 0,
        'total_auto_break_minutes' => 0,
        'total_extra_break_minutes' => 0,
        'total_payable_minutes' => 0,
        'total_payable_label' => '0:00',
        'total_gross_pay_yen' => 0,
    ];
    $period = $period ?? ['period_start' => null, 'period_end' => null];
    $company = $company ?? [];
    $displayName = $displayName ?? 'スタッフ';
    $hourlyWage = (int)($user->hourly_wage ?? 0);
    $autoPrint = (bool)($autoPrint ?? false);
    $payment = $payment ?? null;
    $form = $form ?? [
        'pay_type' => 'monthly',
        'tax_year' => (int)now()->format('Y'),
        'column_type' => 'kou',
        'dep_count' => 0,
        'social_insurance_amount' => 0,
    ];
    $withholding = $withholding ?? [
        'gross_pay' => (int)($totals['total_gross_pay_yen'] ?? 0),
        'taxable_amount' => (int)($totals['total_gross_pay_yen'] ?? 0),
        'withholding_tax' => 0,
        'social_insurance_amount' => 0,
        'net_pay' => (int)($totals['total_gross_pay_yen'] ?? 0),
        'has_tax_row' => false,
    ];
    $paySchedule = $paySchedule ?? ['closing_date' => null, 'payment_date' => null];
@endphp
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>給与支払明細書 {{ $displayName }} {{ $month }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Sans", "Yu Gothic", "Meiryo", sans-serif; margin: 0; color: #111827; background: #f8fafc; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 0; font-size: 16px; }
        .muted { color: #6b7280; font-size: 12px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; }
        .box { flex: 1 1 200px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; background: #fff; }
        .val { font-size: 20px; font-weight: 700; }
        .btns { display: flex; gap: 8px; margin-bottom: 12px; }
        .btn { display: inline-block; text-decoration: none; border: 1px solid #d1d5db; border-radius: 10px; padding: 8px 12px; color: #111827; background: #fff; font-size: 13px; }
        .btn.primary { background: #111827; color: #fff; border-color: #111827; }
        .field { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: 13px; background: #fff; }
        .field-row { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 8px; }
        .field-label { display: block; margin-bottom: 4px; color: #4b5563; font-size: 12px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; vertical-align: top; }
        th.right, td.right { text-align: right; }
        .warn { font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 10px; }
        .ok { font-size: 12px; color: #065f46; background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 10px; }
        .err { font-size: 12px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px; margin-bottom: 10px; }
        @media (min-width: 768px) {
            .field-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .wrap { max-width: none; padding: 0; }
            .card { border-radius: 0; border: none; padding: 8px 0; }
            table { font-size: 11px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="btns no-print">
        <a class="btn" href="{{ route('admin.payroll.index', ['month' => $month]) }}">← 一覧へ戻る</a>
        <a class="btn" href="{{ route('admin.payroll.withholding.index', ['year' => (int)substr($month, 0, 4)]) }}">源泉税テーブル取込</a>
        <a class="btn" href="{{ route('admin.users.index') }}">ユーザー管理</a>
        <a class="btn primary" href="{{ route('admin.payroll.show', ['user' => $user->id, 'month' => $month, 'print' => 1]) }}">PDF保存（印刷）</a>
    </div>

    <div class="card">
        <h1>給与支払明細書</h1>
        <div class="muted">対象期間：{{ $period['period_start'] ?? '—' }} ～ {{ $period['period_end'] ?? '—' }}（{{ $month }}）</div>
        <div class="muted">締日：{{ $paySchedule['closing_date'] ?? '—' }}（月末締め） / 支給日：{{ $paySchedule['payment_date'] ?? '—' }}（毎月25日）</div>
    </div>

    <div class="card">
        <div class="row">
            <div class="box">
                <div class="muted">事業者</div>
                <div>{{ $company['name'] ?? '' }}</div>
                <div>〒{{ $company['zipcode'] ?? '' }}</div>
                <div>{{ $company['address'] ?? '' }}</div>
                <div>代表：{{ $company['representative'] ?? '' }}</div>
            </div>
            <div class="box">
                <div class="muted">従業員</div>
                <div>{{ $displayName }}</div>
                <div>{{ $user->email }}</div>
                <div>時給：{{ $hourlyWage > 0 ? number_format($hourlyWage).'円' : '未設定' }}</div>
                @if(!empty($user->bank_name) || !empty($user->bank_branch_name) || !empty($user->bank_account_number))
                    <div class="muted" style="margin-top:6px;">振込先</div>
                    <div>{{ $user->bank_name ?? '' }} {{ $user->bank_branch_name ?? '' }}</div>
                    <div>{{ $user->bank_account_type ?? '' }} {{ $user->bank_account_number ?? '' }}</div>
                    <div>{{ $user->bank_account_holder_kana ?? '' }}</div>
                @endif
            </div>
            <div class="box">
                <div class="muted">支給見込額（総支給）</div>
                <div class="val">{{ number_format((int)($totals['total_gross_pay_yen'] ?? 0)) }}円</div>
                <div class="muted">給与対象時間：{{ $totals['total_payable_label'] ?? '0:00' }}（15分単位）</div>
                @if($payment?->confirmed_at)
                    <div class="muted" style="margin-top:6px;">最終更新：{{ $payment->confirmed_at->format('Y-m-d H:i') }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card no-print">
        <h2>源泉所得税（給与）設定</h2>
        <p class="muted">税額表（{{ (int)$form['tax_year'] }}年 / {{ $form['pay_type'] === 'daily' ? '日額表' : '月額表' }} / {{ $form['column_type'] === 'kou' ? '甲欄' : '乙欄' }}）を参照して自動計算します。</p>

        @if (session('success'))
            <div class="ok" style="margin-top:8px;">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="err" style="margin-top:8px;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.payroll.payment.save', ['user' => $user->id]) }}" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <div class="field-row">
                <div>
                    <label class="field-label" for="pay_type">pay_type</label>
                    <select id="pay_type" name="pay_type" class="field">
                        <option value="monthly" @selected((string)$form['pay_type'] === 'monthly')>monthly（月額）</option>
                        <option value="daily" @selected((string)$form['pay_type'] === 'daily')>daily（日額）</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="tax_year">税額表 年度</label>
                    <input id="tax_year" name="tax_year" type="number" min="2000" max="2100" class="field" value="{{ (int)$form['tax_year'] }}">
                </div>
                <div>
                    <label class="field-label" for="column_type">扶養控除等申告書（甲欄/乙欄）</label>
                    <select id="column_type" name="column_type" class="field">
                        <option value="kou" @selected((string)$form['column_type'] === 'kou')>提出済み（甲欄）</option>
                        <option value="otsu" @selected((string)$form['column_type'] === 'otsu')>未提出（乙欄）</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="dep_count">扶養人数（甲欄のみ）</label>
                    <input id="dep_count" name="dep_count" type="number" min="0" max="15" class="field" value="{{ (int)$form['dep_count'] }}">
                </div>
                <div>
                    <label class="field-label" for="social_insurance_amount">社会保険控除額（円）</label>
                    <input id="social_insurance_amount" name="social_insurance_amount" type="number" min="0" max="99999999" class="field" value="{{ (int)$form['social_insurance_amount'] }}">
                </div>
            </div>

            <div style="margin-top:12px;">
                <button type="submit" class="btn primary">源泉税を計算して保存</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>勤怠明細</h2>
        <table>
            <thead>
            <tr>
                <th>日付</th>
                <th>予定</th>
                <th>出勤</th>
                <th>退勤</th>
                <th class="right">自動休憩</th>
                <th class="right">追加休憩</th>
                <th class="right">勤務時間</th>
                <th class="right">給与対象</th>
                <th class="right">支給額</th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $a)
                @php
                    $r = $rows[(int)$a->id] ?? null;
                    $plan = $a->shift ? (($a->shift->start_time ?? '').'〜'.($a->shift->end_time ?? '')) : '';
                    $in  = $a->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '—';
                    $out = $a->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '—';
                    $workLabel = $a->work_time_label ?? \App\Services\PayrollCalculator::minutesToLabel((int)($a->work_minutes ?? 0));
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->attendance_date)->format('Y-m-d') }}</td>
                    <td>{{ $plan }}</td>
                    <td>{{ $in }}</td>
                    <td>{{ $out }}</td>
                    <td class="right">{{ (int)($r['auto_break_minutes'] ?? 0) }}分</td>
                    <td class="right">{{ (int)($r['extra_break_minutes'] ?? 0) }}分</td>
                    <td class="right">{{ $workLabel }}</td>
                    <td class="right">{{ $r['payable_label'] ?? '0:00' }}</td>
                    <td class="right">{{ number_format((int)($r['gross_pay_yen'] ?? 0)) }}円</td>
                </tr>
            @empty
                <tr><td colspan="9" class="right">データなし</td></tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4" class="right">合計</th>
                <th class="right">{{ (int)($totals['total_auto_break_minutes'] ?? 0) }}分</th>
                <th class="right">{{ (int)($totals['total_extra_break_minutes'] ?? 0) }}分</th>
                <th class="right">{{ \App\Services\PayrollCalculator::minutesToLabel((int)($totals['total_work_minutes'] ?? 0)) }}</th>
                <th class="right">{{ $totals['total_payable_label'] ?? '0:00' }}</th>
                <th class="right">{{ number_format((int)($totals['total_gross_pay_yen'] ?? 0)) }}円</th>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="card">
        <h2>支給・控除欄（提出書類向け）</h2>
        <table>
            <tbody>
            <tr><th>基本給（時給×対象時間）</th><td class="right">{{ number_format((int)($withholding['gross_pay'] ?? 0)) }}円</td></tr>
            <tr><th>時間外手当</th><td class="right">0円</td></tr>
            <tr><th>通勤手当</th><td class="right">0円</td></tr>
            <tr><th>社会保険控除</th><td class="right">{{ number_format((int)($withholding['social_insurance_amount'] ?? 0)) }}円</td></tr>
            <tr><th>課税対象額（総支給-社会保険）</th><td class="right">{{ number_format((int)($withholding['taxable_amount'] ?? 0)) }}円</td></tr>
            <tr><th>源泉所得税（税額表）</th><td class="right">{{ number_format((int)($withholding['withholding_tax'] ?? 0)) }}円</td></tr>
            <tr><th>控除合計（税・社保）</th><td class="right">{{ number_format((int)(($withholding['social_insurance_amount'] ?? 0) + ($withholding['withholding_tax'] ?? 0))) }}円</td></tr>
            <tr><th>差引支給額</th><td class="right">{{ number_format((int)($withholding['net_pay'] ?? 0)) }}円</td></tr>
            </tbody>
        </table>
    </div>

    @if(!($withholding['has_tax_row'] ?? false))
        <div class="warn">
            該当する源泉徴収税額表のレンジが見つかりませんでした。税額は0円として表示しています。<br>
            「源泉税テーブル取込」画面で対象年データを取り込み、設定値（甲欄/乙欄・扶養人数・pay_type）を確認してください。
        </div>
    @endif
</div>

@if($autoPrint)
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
