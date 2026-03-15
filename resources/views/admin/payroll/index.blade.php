@php
    $month = $month ?? now()->format('Y-m');
    $q = $q ?? '';
    $rows = $rows ?? [];
    $period = $period ?? ['period_start' => null, 'period_end' => null];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">従業員給与一覧</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.payroll.withholding.index', ['year' => (int)substr($month, 0, 4)]) }}"
                   class="text-sm text-indigo-700 underline hover:text-indigo-900">源泉税テーブル取込</a>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">ユーザー管理へ</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.payroll.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">対象月</label>
                        <input type="month" name="month" value="{{ $month }}" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">検索</label>
                        <input type="text" name="q" value="{{ $q }}" placeholder="氏名 / ふりがな / メール" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            表示
                        </button>
                        <a href="{{ route('admin.payroll.index', ['month' => $month]) }}" class="text-sm text-gray-600 underline hover:text-gray-900">リセット</a>
                    </div>
                </form>

                <div class="mt-3 text-sm text-gray-600">
                    集計期間：{{ $period['period_start'] ?? '—' }} ～ {{ $period['period_end'] ?? '—' }}
                    <span class="ml-3">端数処理：15分未満切り捨て</span>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-3">氏名</th>
                            <th class="text-right px-4 py-3">時給</th>
                            <th class="text-right px-4 py-3">勤務日数</th>
                            <th class="text-right px-4 py-3">勤務時間</th>
                            <th class="text-right px-4 py-3">自動休憩</th>
                            <th class="text-right px-4 py-3">追加休憩</th>
                            <th class="text-right px-4 py-3">給与対象時間</th>
                            <th class="text-right px-4 py-3">支給見込額</th>
                            <th class="text-right px-4 py-3">源泉所得税</th>
                            <th class="text-right px-4 py-3">差引支給額</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $r)
                            @php($u = $r['user'])
                            @php($payment = $r['payment'] ?? null)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.payroll.show', ['user' => $u->id, 'month' => $month]) }}"
                                       class="font-semibold text-indigo-700 underline hover:text-indigo-900">
                                        {{ $r['display_name'] }}
                                    </a>
                                    <div class="text-xs text-gray-500">{{ $u->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ $r['hourly_wage'] > 0 ? number_format((int)$r['hourly_wage']).'円' : '未設定' }}</td>
                                <td class="px-4 py-3 text-right">{{ (int)$r['worked_days'] }}日</td>
                                <td class="px-4 py-3 text-right">{{ \App\Services\PayrollCalculator::minutesToLabel((int)($r['totals']['total_work_minutes'] ?? 0)) }}</td>
                                <td class="px-4 py-3 text-right">{{ (int)($r['totals']['total_auto_break_minutes'] ?? 0) }}分</td>
                                <td class="px-4 py-3 text-right">{{ (int)($r['totals']['total_extra_break_minutes'] ?? 0) }}分</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ $r['totals']['total_payable_label'] ?? '0:00' }}</td>
                                <td class="px-4 py-3 text-right font-extrabold text-emerald-700">{{ number_format((int)($r['totals']['total_gross_pay_yen'] ?? 0)) }}円</td>
                                <td class="px-4 py-3 text-right">{{ $payment ? number_format((int)$payment->withholding_tax).'円' : '未確定' }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ $payment ? number_format((int)$payment->net_pay).'円' : '未確定' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">対象データがありません。</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
