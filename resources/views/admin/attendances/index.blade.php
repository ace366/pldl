{{-- resources/views/admin/attendances/index.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    // できるだけ柔軟に受け取る（Controllerの変数名差異に耐える）
    $date   = $date   ?? request('date', now()->toDateString());
    $baseId = (int)($baseId ?? request('base_id', 0));
    $bases  = $bases  ?? collect();
    $statusLabels = [
        'scheduled' => '予定',
        'working' => '勤務中',
        'done' => '完了',
        'completed' => '完了',
        'absent' => '欠勤',
        'canceled' => '取消',
    ];

    // 実績レコード（ShiftAttendance想定）
    $attendances = $attendances ?? ($records ?? ($items ?? collect()));

    // 画面表示用：拠点名
    $baseName = optional($bases->firstWhere('id', $baseId))->name ?? ($baseId ? "拠点#{$baseId}" : '未選択');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="text-xl">🧾</div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    勤怠実績（一覧）
                </h2>
            </div>

            <div class="flex gap-2">
                @if(Route::has('admin.shifts.index'))
                    <a href="{{ route('admin.shifts.index', ['base_id' => $baseId ?: null, 'date' => $date]) }}"
                       class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                        ← シフト一覧へ
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">

            {{-- 説明 --}}
            <div class="mb-6">
                <p class="mt-1 text-sm text-slate-600">
                    日付と会場で絞り込みできます。必要に応じて「シフト一覧」から確認もできます。
                </p>
            </div>

            {{-- 成功メッセージ --}}
            @if (session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900 font-semibold">
                    ✅ {{ session('success') }}
                </div>
            @endif

            {{-- フィルタ --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
                <form method="GET"
                      action="{{ Route::has('admin.attendances.index') ? route('admin.attendances.index') : url()->current() }}"
                      class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">

                    <div>
                        <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                        <select id="base_id" name="base_id"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            <option value="0" @selected($baseId === 0)>（全拠点）</option>
                            @foreach($bases as $b)
                                <option value="{{ $b->id }}" @selected((int)$b->id === $baseId)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-semibold text-slate-700">日付</label>
                        <input id="date" name="date" type="date" value="{{ $date }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                            表示する
                        </button>

                        @if(Route::has('admin.attendances.export_csv'))
                            <a href="{{ route('admin.attendances.export_csv', ['base_id' => $baseId, 'month' => \Carbon\Carbon::parse($date)->format('Y-m')]) }}"
                               class="inline-flex items-center justify-center rounded-2xl bg-white border border-slate-200 px-4 py-3 text-sm font-extrabold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                                CSV
                            </a>
                        @endif
                    </div>
                </form>

                <div class="mt-4 text-sm text-slate-600">
                    表示中：<span class="font-bold">{{ $baseName }}</span> / <span class="font-bold">{{ $date }}</span>
                </div>
            </div>

            {{-- 一覧 --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="font-semibold">実績一覧</div>
                    <div class="text-xs text-slate-500">
                        件数：{{ is_countable($attendances) ? count($attendances) : (method_exists($attendances,'total') ? $attendances->total() : '') }}
                    </div>
                </div>

                <div class="p-5">
                    {{-- PCテーブル --}}
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th class="py-2 pr-4">日付</th>
                                    <th class="py-2 pr-4">会場</th>
                                    <th class="py-2 pr-4">スタッフ</th>
                                    <th class="py-2 pr-4">状態</th>
                                    <th class="py-2 pr-4">休憩（分）</th>
                                    <th class="py-2 pr-4">勤務（分）</th>
                                    <th class="py-2 pr-4">ロック</th>
                                    <th class="py-2 pr-4">操作</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @forelse($attendances as $a)
                                    @php
                                        $aDate = data_get($a, 'attendance_date') ?: data_get($a, 'shift_date') ?: '';
                                        $aBaseId = (int)(data_get($a, 'base_id') ?? 0);
                                        $aBaseName = optional($bases->firstWhere('id', $aBaseId))->name ?? ($aBaseId ? "拠点#{$aBaseId}" : '-');

                                        $user = data_get($a, 'user');
                                        $userName =
                                            $user
                                                ? (trim((string)data_get($user,'last_name').' '.(string)data_get($user,'first_name')) ?: (data_get($user,'name') ?? data_get($user,'email') ?? ('ユーザー#'.data_get($user,'id'))))
                                                : ('ユーザー#'.(data_get($a,'user_id') ?? ''));
                                        $statusRaw = (string)(data_get($a,'status') ?? '');
                                        $status = $statusLabels[$statusRaw] ?? ($statusRaw !== '' ? $statusRaw : '-');
                                        $breakMin = (int)(data_get($a,'break_minutes') ?? data_get($a,'auto_break_minutes') ?? 0);
                                        $workMin  = (int)(data_get($a,'work_minutes') ?? 0);
                                        $locked   = (bool)(data_get($a,'is_locked') ?? false);

                                        $shiftId = data_get($a,'shift_id');
                                    @endphp

                                    <tr>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $aDate }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $aBaseName }}</td>
                                        <td class="py-3 pr-4">{{ $userName }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold">
                                                {{ $status }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $breakMin }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $workMin }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $locked ? 'ON' : 'OFF' }}</td>
                                        <td class="py-3 pr-4 whitespace-nowrap">
                                            @if(Route::has('admin.attendances.edit'))
                                                <a class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-bold shadow-sm hover:bg-slate-50"
                                                   href="{{ route('admin.attendances.edit', $a) }}">
                                                    実績を編集
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-slate-600">
                                            データがありません。
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- スマホカード --}}
                    <div class="lg:hidden space-y-3">
                        @forelse($attendances as $a)
                            @php
                                $aDate = data_get($a, 'attendance_date') ?: data_get($a, 'shift_date') ?: '';
                                $aBaseId = (int)(data_get($a, 'base_id') ?? 0);
                                $aBaseName = optional($bases->firstWhere('id', $aBaseId))->name ?? ($aBaseId ? "拠点#{$aBaseId}" : '-');

                                $user = data_get($a, 'user');
                                $userName =
                                    $user
                                        ? (trim((string)data_get($user,'last_name').' '.(string)data_get($user,'first_name')) ?: (data_get($user,'name') ?? data_get($user,'email') ?? ('ユーザー#'.data_get($user,'id'))))
                                        : ('ユーザー#'.(data_get($a,'user_id') ?? ''));
                                $statusRaw = (string)(data_get($a,'status') ?? '');
                                $status = $statusLabels[$statusRaw] ?? ($statusRaw !== '' ? $statusRaw : '-');
                                $breakMin = (int)(data_get($a,'break_minutes') ?? data_get($a,'auto_break_minutes') ?? 0);
                                $workMin  = (int)(data_get($a,'work_minutes') ?? 0);
                                $locked   = (bool)(data_get($a,'is_locked') ?? false);
                            @endphp

                            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-xs text-slate-500">日付</div>
                                        <div class="text-lg font-extrabold">{{ $aDate }}</div>
                                        <div class="mt-2 text-sm text-slate-700 font-semibold">{{ $userName }}</div>
                                        <div class="text-sm text-slate-600">{{ $aBaseName }}</div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-xs text-slate-500">状態</div>
                                        <div class="mt-1 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold">
                                            {{ $status }}
                                        </div>
                                        <div class="mt-3 text-xs text-slate-500">ロック</div>
                                        <div class="text-sm font-bold">{{ $locked ? 'ON' : 'OFF' }}</div>
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">休憩（分）</div>
                                        <div class="text-lg font-extrabold">{{ $breakMin }}</div>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">勤務（分）</div>
                                        <div class="text-lg font-extrabold">{{ $workMin }}</div>
                                    </div>
                                </div>

                                @if(Route::has('admin.attendances.edit'))
                                    <div class="mt-3">
                                        <a class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-bold shadow-sm hover:bg-slate-50"
                                           href="{{ route('admin.attendances.edit', $a) }}">
                                            実績を編集
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-sm text-slate-600">
                                データがありません。
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
