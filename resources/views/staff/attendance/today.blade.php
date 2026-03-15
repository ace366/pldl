{{-- resources/views/staff/attendance/today.blade.php --}}
@php
    $date = $date ?? now()->toDateString();
    $base = $base ?? null;
    $shift = $shift ?? null;
    $attendance = $attendance ?? null;
    $message = $message ?? null;

    $staffName = $staffName ?? (auth()->user()->name ?? trim((auth()->user()->last_name ?? '').' '.(auth()->user()->first_name ?? '')) ?? 'スタッフ');

    $clockIn  = $attendance?->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : null;
    $clockOut = $attendance?->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : null;

    $hasAttendance = (bool)$attendance && (bool)($attendance->id ?? null);

    $canIn  = (bool)$shift && $hasAttendance && !$clockIn;
    $canOut = (bool)$shift && $hasAttendance && (bool)$clockIn && !$clockOut;

    // ✅ 状態（英語→日本語）
    $statusRaw = (string)($attendance?->status ?? ($shift?->status ?? ''));
    $statusMap = [
        'scheduled' => '予定',
        'working'   => '勤務中',
        'done'      => '完了',
        'completed' => '完了',
        'absent'    => '欠勤',
        'canceled'  => '取消',
    ];
    $statusJa = $statusMap[$statusRaw] ?? ($statusRaw !== '' ? $statusRaw : '—');

    // ルート生成（attendance が無いと例外になるのでガード）
    $clockInAction  = $hasAttendance && \Illuminate\Support\Facades\Route::has('staff.attendance.clock_in')
        ? route('staff.attendance.clock_in', ['shiftAttendance' => $attendance->id])
        : null;

    $clockOutAction = $hasAttendance && \Illuminate\Support\Facades\Route::has('staff.attendance.clock_out')
        ? route('staff.attendance.clock_out', ['shiftAttendance' => $attendance->id])
        : null;

    $csrf = csrf_token();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="text-xl">🕒</div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    今日の勤怠（打刻）
                </h2>
            </div>

            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                <span class="font-semibold">{{ $staffName }}</span>
                <span>・</span>
                <span>{{ $date }}</span>
            </div>
        </div>
    </x-slot>

    <div class="bg-slate-50 py-4 sm:py-8 pb-24 sm:pb-8">
        <div class="max-w-3xl mx-auto px-4 space-y-3 sm:space-y-4">

            {{-- フラッシュ --}}
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 font-semibold">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 font-semibold">
                    ⚠️ {{ session('error') }}
                </div>
            @endif
            @if($message)
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-indigo-800 font-semibold">
                    ℹ️ {{ $message }}
                </div>
            @endif

            {{-- ✅ React：時刻 + 正方形ボタン（このページだけ） --}}
            <div
                id="react-staff-attendance-today"
                data-clock-in-action="{{ $clockInAction ?? '' }}"
                data-clock-out-action="{{ $clockOutAction ?? '' }}"
                data-can-in="{{ $canIn ? '1' : '0' }}"
                data-can-out="{{ $canOut ? '1' : '0' }}"
                data-csrf="{{ $csrf }}"
            ></div>

            {{-- 情報カード（既存のまま） --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xs text-slate-500">きょう</div>
                            <div class="text-2xl font-extrabold text-slate-900">{{ $date }}</div>
                            <div class="mt-1 text-sm text-slate-600">
                                会場：<span class="font-bold">{{ $base?->name ?? '（未設定）' }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-slate-500">あなた</div>
                            <div class="text-lg font-extrabold">{{ $staffName }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 space-y-3 sm:space-y-4">
                    {{-- シフト情報 --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-sm font-bold text-slate-800 mb-2">📌 今日のシフト</div>

                        @if($shift)
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                <div class="rounded-xl bg-white border border-slate-200 px-3 py-3">
                                    <div class="text-xs text-slate-500">開始</div>
                                    <div class="text-lg font-extrabold">{{ $shift->start_time ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-white border border-slate-200 px-3 py-3">
                                    <div class="text-xs text-slate-500">終了</div>
                                    <div class="text-lg font-extrabold">{{ $shift->end_time ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-white border border-slate-200 px-3 py-3">
                                    <div class="text-xs text-slate-500">状態</div>
                                    <div class="text-lg font-extrabold">{{ $statusJa }}</div>
                                </div>
                            </div>
                        @else
                            <div class="text-sm text-slate-700 leading-relaxed">
                                今日はシフトが登録されていません。<br>
                                （必要なら管理者に連絡してください）
                            </div>
                        @endif
                    </div>

                    {{-- 打刻状況 --}}
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <div class="text-sm font-bold text-slate-800">✅ 打刻状況</div>

                            @if($clockIn && $clockOut)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-700">
                                    ✔ 完了
                                </span>
                            @elseif($clockIn)
                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 border border-indigo-200 px-3 py-1 text-xs font-bold text-indigo-700">
                                    ⏳ 退勤待ち
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-600">
                                    未打刻
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <div class="text-xs text-slate-500">出勤</div>
                                <div class="text-2xl font-extrabold {{ $clockIn ? 'text-emerald-700' : 'text-slate-700' }}">
                                    {{ $clockIn ? $clockIn : '未' }}
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <div class="text-xs text-slate-500">退勤</div>
                                <div class="text-2xl font-extrabold {{ $clockOut ? 'text-indigo-700' : 'text-slate-700' }}">
                                    {{ $clockOut ? $clockOut : '未' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if(\Illuminate\Support\Facades\Route::has('staff.attendance.qr'))
                                <a href="{{ route('staff.attendance.qr') }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                                    📷 QR打刻へ
                                </a>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('staff.attendance.history'))
                                <a href="{{ route('staff.attendance.history') }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                                    📚 勤怠履歴へ
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-500">
                ※打刻は修正できません。間違えた場合は管理者へ連絡してください。
            </div>

        </div>
    </div>
</x-app-layout>
