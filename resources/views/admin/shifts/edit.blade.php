{{-- resources/views/admin/shifts/edit.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    // $bases: Base一覧
    // $staffUsers: 拠点所属スタッフ(User)一覧
    // $shift: Shift（attendanceロード済み）

    $statusLabels = [
        'scheduled' => '予定',
        'working'   => '勤務中',
        'done'      => '完了',
        'absent'    => '欠勤',
        'canceled'  => '取消',
    ];

    $statusHelp = '予定: これから勤務 / 勤務中: 出勤済み / 完了: 退勤済み / 欠勤: 来なかった / 取消: シフト取り消し';
@endphp

<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>シフト編集</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@tailwindcss/line-clamp@latest"></script>
</head>

<body class="bg-slate-50 text-slate-900">
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- ヘッダー --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2">
                <div class="text-2xl">🗓️</div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">シフトを編集</h1>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                会場・担当者・時間などを変更して、<span class="font-semibold">「保存する」</span>を押してください。
            </p>
        </div>

        <div class="flex gap-2">
            @if(Route::has('admin.shifts.index'))
                <a href="{{ route('admin.shifts.index', ['base_id' => $shift->base_id, 'date' => optional($shift->shift_date)->format('Y-m-d')]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 一覧へ戻る
                </a>
            @endif
        </div>
    </div>

    {{-- 成功メッセージ --}}
    @if (session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- バリデーションエラー --}}
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

    {{-- フォーム --}}
    <form method="POST" action="{{ route('admin.shifts.update', $shift) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="font-semibold">入力フォーム</div>
                <div class="text-xs text-slate-500">
                    シフトID: {{ $shift->id }}
                </div>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- 会場（拠点） --}}
                    <div>
                        <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                        <div class="mt-1 text-xs text-slate-500">どの会場の勤務かを選びます</div>

                        <select id="base_id" name="base_id"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @foreach(($bases ?? collect()) as $b)
                                <option value="{{ $b->id }}" @selected((int)old('base_id', $shift->base_id) === (int)$b->id)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('base_id')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 担当スタッフ（ユーザー） --}}
                    <div>
                        <label for="user_id" class="block text-sm font-semibold text-slate-700">担当スタッフ</label>
                        <div class="mt-1 text-xs text-slate-500">その会場に所属するスタッフから選びます</div>

                        <select id="user_id" name="user_id"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @foreach(($staffUsers ?? collect()) as $u)
                                @php
                                    // PLDLのUserカラムに合わせて表示名を組み立て（無ければ name / email）
                                    $display =
                                        trim(($u->last_name ?? '').' '.($u->first_name ?? '')) !== ''
                                            ? trim(($u->last_name ?? '').' '.($u->first_name ?? ''))
                                            : ($u->name ?? ($u->email ?? ('ユーザー#'.$u->id)));
                                @endphp
                                <option value="{{ $u->id }}" @selected((int)old('user_id', $shift->user_id) === (int)$u->id)>
                                    {{ $display }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-1 text-xs text-amber-700">
                            ※ 拠点を変更した場合、この候補は「その拠点のスタッフ」になります（必要なら作り直してください）
                        </div>

                        @error('user_id')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 日付 --}}
                    <div>
                        <label for="shift_date" class="block text-sm font-semibold text-slate-700">勤務日</label>
                        <div class="mt-1 text-xs text-slate-500">例：2026-01-24</div>

                        <input id="shift_date" name="shift_date" type="date"
                               value="{{ old('shift_date', optional($shift->shift_date)->format('Y-m-d')) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                        @error('shift_date')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 状態 --}}
                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700">状態</label>
                        <div class="mt-1 text-xs text-slate-500">{{ $statusHelp }}</div>

                        <select id="status" name="status"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @php($curStatus = (string)old('status', $shift->status ?? 'scheduled'))
                            @foreach($statusLabels as $key => $jp)
                                <option value="{{ $key }}" @selected($curStatus === $key)>{{ $jp }}</option>
                            @endforeach
                            @if(!array_key_exists($curStatus, $statusLabels))
                                <option value="{{ $curStatus }}" selected>{{ $curStatus }}（現状の値）</option>
                            @endif
                        </select>

                        @error('status')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 開始時間 --}}
                    <div>
                        <label for="start_time" class="block text-sm font-semibold text-slate-700">開始時間</label>
                        <div class="mt-1 text-xs text-slate-500">例：14:00</div>

                        <input id="start_time" name="start_time" type="time"
                               value="{{ old('start_time', is_string($shift->start_time) ? substr($shift->start_time,0,5) : (optional($shift->start_time)->format('H:i') ?? '')) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                        @error('start_time')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 終了時間 --}}
                    <div>
                        <label for="end_time" class="block text-sm font-semibold text-slate-700">終了時間</label>
                        <div class="mt-1 text-xs text-slate-500">例：18:00</div>

                        <input id="end_time" name="end_time" type="time"
                               value="{{ old('end_time', is_string($shift->end_time) ? substr($shift->end_time,0,5) : (optional($shift->end_time)->format('H:i') ?? '')) }}"
                               class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                        @error('end_time')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 備考 --}}
                    <div class="sm:col-span-2">
                        <label for="note" class="block text-sm font-semibold text-slate-700">備考</label>
                        <div class="mt-1 text-xs text-slate-500">引き継ぎ・特記事項があれば入力</div>

                        <textarea id="note" name="note" rows="4"
                                  class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                  placeholder="例：15:00〜16:00は会議のため席外します">{{ old('note', $shift->note) }}</textarea>

                        @error('note')
                        <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- ボタン --}}
                <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-end">
                    @if(Route::has('admin.shifts.index'))
                        <a href="{{ route('admin.shifts.index', ['base_id' => $shift->base_id, 'date' => optional($shift->shift_date)->format('Y-m-d')]) }}"
                           class="inline-flex justify-center items-center rounded-2xl bg-white border border-slate-200 px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                            変更をやめる
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                        💾 保存する
                    </button>
                </div>

            </div>
        </div>

        {{-- 参考：実績（ShiftAttendance） --}}
        @if($shift->attendance)
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <div class="font-semibold">実績（参考）</div>
                    <div class="text-xs text-slate-500 mt-1">ここは表示のみ。実績編集は別画面で対応予定でもOK。</div>
                </div>
                <div class="p-5 text-sm text-slate-700">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                            <div class="text-xs text-slate-500">休憩（分）</div>
                            <div class="text-lg font-extrabold">{{ (int)$shift->attendance->break_minutes }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                            <div class="text-xs text-slate-500">勤務（分）</div>
                            <div class="text-lg font-extrabold">{{ (int)$shift->attendance->work_minutes }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4">
                            <div class="text-xs text-slate-500">ロック</div>
                            <div class="text-lg font-extrabold">{{ $shift->attendance->is_locked ? 'ON' : 'OFF' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </form>

</div>
</body>
</html>
