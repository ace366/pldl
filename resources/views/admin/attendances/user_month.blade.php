{{-- resources/views/admin/attendances/user_month.blade.php --}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Route;

    $month = (string)($month ?? request('month', now()->format('Y-m')));
    $baseId = (int)($baseId ?? request('base_id', 0));
    $bases = $bases ?? collect();
    $items = $items ?? collect();
    $isClosed = (bool)($isClosed ?? false);

    $baseName = optional($bases->firstWhere('id', $baseId))->name ?? ($baseId ? '拠点#'.$baseId : '未選択');
    $userName = $user
        ? (trim(($user->last_name ?? '').' '.($user->first_name ?? '')) ?: ($user->name ?? $user->email ?? ('ユーザー#'.$user->id)))
        : 'スタッフ';

    $minutesToLabel = static function ($minutes) {
        $minutes = max(0, (int)$minutes);
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    };
    $statusLabels = [
        'scheduled' => '予定',
        'working' => '勤務中',
        'done' => '完了',
        'completed' => '完了',
        'absent' => '欠勤',
        'canceled' => '取消',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="text-xl">👤</div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">スタッフ別 勤怠（月次）</h2>
            </div>
            @if(Route::has('admin.attendances.index'))
                <a href="{{ route('admin.attendances.index', ['base_id' => $baseId, 'date' => $month.'-01']) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 日別一覧へ
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5">
                <form method="GET"
                      action="{{ route('admin.attendances.user_month', ['user' => $user->id]) }}"
                      class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                        <select id="base_id" name="base_id"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @foreach($bases as $b)
                                <option value="{{ $b->id }}" @selected((int)$b->id === $baseId)>{{ $b->name ?? ('拠点#'.$b->id) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="month" class="block text-sm font-semibold text-slate-700">対象月</label>
                        <input id="month" name="month" type="month" value="{{ $month }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <button type="submit"
                            class="inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                        表示する
                    </button>
                </form>
            </div>

            @if($isClosed)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                    🔒 この月は締め済みです（閲覧のみ）。
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">スタッフ</div>
                    <div class="text-lg font-extrabold">{{ $userName }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">会場</div>
                    <div class="text-lg font-extrabold">{{ $baseName }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs text-slate-500">合計勤務時間</div>
                    <div class="text-lg font-extrabold">{{ $totalLabel ?? $minutesToLabel($totalMinutes ?? 0) }}</div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="font-semibold">勤務明細</div>
                    <div class="text-xs text-slate-500">件数：{{ count($items) }}</div>
                </div>

                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th class="py-2 pr-4">日付</th>
                                    <th class="py-2 pr-4">予定</th>
                                    <th class="py-2 pr-4">出退勤</th>
                                    <th class="py-2 pr-4">状態</th>
                                    <th class="py-2 pr-4">自動休憩</th>
                                    <th class="py-2 pr-4">追加休憩</th>
                                    <th class="py-2 pr-4">勤務</th>
                                    <th class="py-2 pr-4">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($items as $a)
                                    @php
                                        $date = $a->attendance_date ? Carbon::parse($a->attendance_date)->format('Y-m-d') : '-';
                                        $scheduled = $a->shift ? (($a->shift->start_time ?? '--:--').'〜'.($a->shift->end_time ?? '--:--')) : '-';
                                        $clockIn = $a->clock_in_at ? Carbon::parse($a->clock_in_at)->format('H:i') : '--:--';
                                        $clockOut = $a->clock_out_at ? Carbon::parse($a->clock_out_at)->format('H:i') : '--:--';
                                        $statusRaw = (string)($a->status ?? '');
                                        $status = $statusLabels[$statusRaw] ?? ($statusRaw !== '' ? $statusRaw : '-');
                                    @endphp
                                    <tr>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $date }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $scheduled }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $clockIn }}〜{{ $clockOut }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $status }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ (int)($a->auto_break_minutes ?? 0) }}分</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ (int)($a->break_minutes ?? 0) }}分</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $minutesToLabel((int)($a->work_minutes ?? 0)) }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">
                                            @if(Route::has('admin.attendances.edit'))
                                                <a href="{{ route('admin.attendances.edit', $a->id) }}"
                                                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-bold shadow-sm hover:bg-slate-50">
                                                    編集
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-slate-600">データがありません。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
