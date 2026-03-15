{{-- resources/views/staff/attendance/history.blade.php --}}
@php
    $month = $month ?? now()->format('Y-m');
    $items = $items ?? collect();
    $totalLabel = $totalLabel ?? null;
    $baseName = $baseName ?? null;

    $staffName = $staffName ?? (auth()->user()->name ?? trim((auth()->user()->last_name ?? '').' '.(auth()->user()->first_name ?? '')) ?? 'スタッフ');
    $hourlyWage = (int)($hourlyWage ?? 0);
    $payrollRows = $payrollRows ?? [];
    $payrollTotals = $payrollTotals ?? [
        'total_work_minutes' => 0,
        'total_auto_break_minutes' => 0,
        'total_extra_break_minutes' => 0,
        'total_payable_minutes_raw' => 0,
        'total_payable_minutes' => 0,
        'total_payable_label' => '0:00',
        'total_gross_pay_yen' => 0,
    ];
    $statusMap = [
        'scheduled' => '予定',
        'working' => '勤務中',
        'completed' => '完了',
        'done' => '完了',
        'absent' => '欠勤',
        'canceled' => '取消',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="text-xl">📚</div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    勤怠履歴
                </h2>
            </div>
            <a href="{{ route('staff.attendance.today') }}"
               class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">
                ← 今日へ
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50">
        <div class="max-w-4xl mx-auto px-4">

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
                <form method="GET" action="{{ route('staff.attendance.history') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-slate-700">月</label>
                        <input type="month" name="month" value="{{ $month }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <button type="submit"
                            class="inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                        表示する
                    </button>
                </form>

                <div class="mt-4 text-sm text-slate-600">
                    スタッフ：<span class="font-bold">{{ $staffName }}</span>
                    @if($baseName)
                        / 会場：<span class="font-bold">{{ $baseName }}</span>
                    @endif
                    @if($totalLabel)
                        / 合計：<span class="font-extrabold text-indigo-700">{{ $totalLabel }}</span>
                    @endif
                    / 時給：<span class="font-extrabold text-indigo-700">{{ $hourlyWage > 0 ? number_format($hourlyWage).'円' : '未設定' }}</span>
                </div>
                <div class="mt-2 text-sm text-slate-600">
                    給与対象（15分単位）：<span class="font-extrabold text-emerald-700">{{ $payrollTotals['total_payable_label'] ?? '0:00' }}</span>
                    / 見込給与：<span class="font-extrabold text-emerald-700">{{ number_format((int)($payrollTotals['total_gross_pay_yen'] ?? 0)) }}円</span>
                </div>
                <div class="mt-1 text-xs text-slate-500">
                    自動休憩：6時間以上45分、8時間以上60分。追加休憩は給与対象時間から控除されます。
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <div class="font-semibold">明細</div>
                    <div class="text-xs text-slate-500 mt-1">入室・退室が入っていない日は勤務時間が 0 になります</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="text-left px-4 py-3 whitespace-nowrap">日付</th>
                            <th class="text-left px-4 py-3 whitespace-nowrap">予定</th>
                            <th class="text-left px-4 py-3 whitespace-nowrap">入室</th>
                            <th class="text-left px-4 py-3 whitespace-nowrap">退室</th>
                            <th class="text-right px-4 py-3 whitespace-nowrap">休憩</th>
                            <th class="text-right px-4 py-3 whitespace-nowrap">追加休憩</th>
                            <th class="text-right px-4 py-3 whitespace-nowrap">勤務</th>
                            <th class="text-right px-4 py-3 whitespace-nowrap">給与対象</th>
                            <th class="text-right px-4 py-3 whitespace-nowrap">給与(円)</th>
                            <th class="text-left px-4 py-3 whitespace-nowrap">状態</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($items as $a)
                            @php
                                $plan = $a->shift ? (($a->shift->start_time ?? '').'〜'.($a->shift->end_time ?? '')) : '';
                                $in  = $a->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '—';
                                $out = $a->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '—';
                                $workLabel = $a->work_time_label ?? (isset($a->work_minutes) ? sprintf('%d:%02d', intdiv((int)$a->work_minutes, 60), ((int)$a->work_minutes % 60)) : '0:00');
                                $row = $payrollRows[(int)$a->id] ?? null;
                                $extraBreak = (int)($row['extra_break_minutes'] ?? ($a->break_minutes ?? 0));
                                $payableLabel = (string)($row['payable_label'] ?? '0:00');
                                $grossPayYen = (int)($row['gross_pay_yen'] ?? 0);
                                $statusRaw = (string)($a->status ?? '');
                                $statusJa = $statusMap[$statusRaw] ?? ($statusRaw !== '' ? $statusRaw : '-');
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap font-semibold">
                                    {{ \Carbon\Carbon::parse($a->attendance_date)->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $plan }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $in }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $out }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">{{ (int)($a->auto_break_minutes ?? 0) }}分</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">{{ $extraBreak }}分</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-extrabold text-indigo-700">{{ $workLabel }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-emerald-700">{{ $payableLabel }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-emerald-700">{{ number_format($grossPayYen) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $statusJa }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-slate-600">
                                    データがありません。
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
