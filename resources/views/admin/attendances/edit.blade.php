{{-- resources/views/admin/attendances/edit.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    /** @var \App\Models\ShiftAttendance $attendance */
    $attendance = $attendance ?? ($shiftAttendance ?? null);

    $isClosed  = (bool)($isClosed ?? false);
    $yearMonth = $yearMonth ?? '';

    $user = $attendance?->user;
    $base = $attendance?->base;

    $userName = $user
        ? (trim(($user->last_name ?? '').' '.($user->first_name ?? '')) ?: ($user->name ?? $user->email ?? ('ユーザー#'.$user->id)))
        : ('ユーザー#'.($attendance?->user_id ?? '?'));

    $baseName = $base
        ? ($base->name ?? ('拠点#'.$base->id))
        : ('拠点#'.($attendance?->base_id ?? '?'));

    $statusLabels = [
        'scheduled' => '予定',
        'working'   => '勤務中',
        'done'      => '完了',
        'absent'    => '欠勤',
        'canceled'  => '取消',
    ];

    $currentStatus = (string)old('status', $attendance?->status ?? 'scheduled');

    $workLabel = $attendance?->work_time_label
        ?? (function($m){
            $m = (int)($m ?? 0);
            $h = intdiv(max(0,$m), 60);
            $r = max(0,$m) % 60;
            return sprintf('%d:%02d', $h, $r);
        })($attendance?->work_minutes ?? 0);

    $fmtLocal = function($dt){
        if(!$dt) return '';
        try { return \Carbon\Carbon::parse($dt)->format('Y-m-d\TH:i'); } catch(\Throwable $e){ return ''; }
    };
@endphp

<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>勤怠修正</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- ヘッダー --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2">
                <div class="text-2xl">🛠️</div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">勤怠の修正</h1>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                修正内容は履歴に残ります。<span class="font-semibold">修正理由</span>を必ず入力してください。
            </p>
        </div>

        <div class="flex gap-2">
            @if(Route::has('admin.attendances.index'))
                <a href="{{ route('admin.attendances.index', ['base_id' => $attendance?->base_id, 'month' => $yearMonth ?: request('month')]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 月次一覧へ
                </a>
            @endif

            @if($attendance?->user_id && Route::has('admin.attendances.user_month'))
                <a href="{{ route('admin.attendances.user_month', ['user' => $attendance->user_id, 'base_id' => $attendance->base_id, 'month' => $yearMonth]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    👤 スタッフ別へ
                </a>
            @endif
        </div>
    </div>

    {{-- ロック表示 --}}
    @if($isClosed)
        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
            🔒 この月は締め済み、またはロック中のため編集できません。
        </div>
    @endif

    {{-- 成功メッセージ --}}
    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- エラー --}}
    @if ($errors->any())
        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900">
            <div class="font-bold mb-2">入力にエラーがあります</div>
            <ul class="list-disc pl-5 text-sm space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 情報カード --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                <div class="text-xs text-slate-500">会場（拠点）</div>
                <div class="text-lg font-extrabold">{{ $baseName }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                <div class="text-xs text-slate-500">スタッフ</div>
                <div class="text-lg font-extrabold">{{ $userName }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                <div class="text-xs text-slate-500">日付</div>
                <div class="text-lg font-extrabold">{{ optional($attendance?->attendance_date)->format('Y-m-d') ?? (string)($attendance?->attendance_date ?? '') }}</div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">勤務（分）</div>
                <div class="text-lg font-extrabold">{{ (int)($attendance?->work_minutes ?? 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">勤務（時間）</div>
                <div class="text-lg font-extrabold">{{ $workLabel }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">自動休憩（分）</div>
                <div class="text-lg font-extrabold">{{ (int)($attendance?->auto_break_minutes ?? 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">追加休憩（分）</div>
                <div class="text-lg font-extrabold">{{ (int)($attendance?->break_minutes ?? 0) }}</div>
                <div class="text-xs text-slate-500 mt-1">状態</div>
                <div class="text-lg font-extrabold">{{ $statusLabels[$currentStatus] ?? $currentStatus }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="text-xs text-slate-500">予定シフト</div>
                <div class="text-lg font-extrabold">
                    @if($attendance?->shift)
                        {{ ($attendance->shift->start_time ?? '').'〜'.($attendance->shift->end_time ?? '') }}
                    @else
                        -
                    @endif
                </div>
                <div class="text-xs text-slate-500 mt-1">メモ</div>
                <div class="text-sm">{{ $attendance?->note ? \Illuminate\Support\Str::limit($attendance->note, 60) : '—' }}</div>
            </div>
        </div>
    </div>

    {{-- 編集フォーム --}}
    <form method="POST" action="{{ route('admin.attendances.update', $attendance) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="font-semibold">修正内容</div>
                <div class="text-xs text-slate-500">実績ID: {{ $attendance->id }}</div>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- 出勤 --}}
                    <div>
                        <label for="clock_in_at" class="block text-sm font-semibold text-slate-700">出勤時刻</label>
                        <div class="mt-1 text-xs text-slate-500">例：2026-01-24 14:00 → フォームでは日時で入力</div>
                        <input id="clock_in_at" name="clock_in_at" type="datetime-local"
                               value="{{ old('clock_in_at', $fmtLocal($attendance->clock_in_at ?? null)) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                               @disabled($isClosed)>
                        @error('clock_in_at') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>

                    {{-- 退勤 --}}
                    <div>
                        <label for="clock_out_at" class="block text-sm font-semibold text-slate-700">退勤時刻</label>
                        <div class="mt-1 text-xs text-slate-500">出勤・退勤が両方入ると勤務分が自動再計算されます</div>
                        <input id="clock_out_at" name="clock_out_at" type="datetime-local"
                               value="{{ old('clock_out_at', $fmtLocal($attendance->clock_out_at ?? null)) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                               @disabled($isClosed)>
                        @error('clock_out_at') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>

                    {{-- 状態 --}}
                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                        <select id="status" name="status"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                @disabled($isClosed)>
                            @foreach($statusLabels as $key => $jp)
                                <option value="{{ $key }}" @selected(old('status', $attendance->status) === $key)>{{ $jp }}</option>
                            @endforeach
                            @php($raw = (string)old('status', $attendance->status))
                            @if($raw && !array_key_exists($raw, $statusLabels))
                                <option value="{{ $raw }}" selected>{{ $raw }}（現状の値）</option>
                            @endif
                        </select>
                        @error('status') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>

                    {{-- メモ --}}
                    <div>
                        <label for="break_minutes" class="block text-sm font-semibold text-slate-700">追加休憩（分）</label>
                        <div class="mt-1 text-xs text-slate-500">給与計算時に控除する任意休憩です（自動休憩とは別）</div>
                        <input id="break_minutes" name="break_minutes" type="number" min="0" step="1"
                               value="{{ old('break_minutes', (int)($attendance->break_minutes ?? 0)) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                               @disabled($isClosed)>
                        @error('break_minutes') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-semibold text-slate-700">メモ</label>
                        <input id="note" name="note" type="text"
                               value="{{ old('note', $attendance->note) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                               placeholder="例：遅刻のため出勤時刻を修正"
                               @disabled($isClosed)>
                        @error('note') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>

                    {{-- 修正理由（必須） --}}
                    <div class="sm:col-span-2">
                        <label for="reason" class="block text-sm font-semibold text-slate-700">修正理由（必須）</label>
                        <div class="mt-1 text-xs text-slate-500">監査用に残します。短くてOK。</div>
                        <input id="reason" name="reason" type="text"
                               value="{{ old('reason') }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                               placeholder="例：管理者確認のうえ、打刻漏れを反映"
                               @disabled($isClosed)>
                        @error('reason') <div class="mt-1 text-xs text-rose-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- ボタン --}}
                <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-end">
                    @if(Route::has('admin.attendances.index'))
                        <a href="{{ route('admin.attendances.index', ['base_id' => $attendance->base_id, 'month' => $yearMonth]) }}"
                           class="inline-flex justify-center items-center rounded-2xl bg-white border border-slate-200 px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                            戻る
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed"
                            @disabled($isClosed)>
                        💾 修正を保存
                    </button>
                </div>

                @if($isClosed)
                    <div class="mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3">
                        ※ 締め済み（またはロック）なので編集できません。
                    </div>
                @endif
            </div>
        </div>
    </form>

</div>
</body>
</html>
