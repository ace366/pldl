{{-- resources/views/admin/closings/index.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;
    use Carbon\Carbon;

    $bases   = $bases ?? collect();
    $month   = (string)($month ?? request('month', now()->format('Y-m')));
    $baseId  = (int)($baseId ?? request('base_id', 0));
    $closing = $closing ?? null;         // レコード（あれば）
    $recent  = $recent ?? collect();     // 直近

    // ✅ Controller から渡される想定。無ければモデルで計算（保険）
    if (!isset($isClosed)) {
        $isClosed = false;
        if ($baseId > 0 && class_exists(\App\Models\AttendanceClosing::class)) {
            $isClosed = \App\Models\AttendanceClosing::isClosed($baseId, $month);
        }
    }

    try {
        $dt = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    } catch (\Throwable $e) {
        $dt = now()->startOfMonth();
        $month = $dt->format('Y-m');
    }
    $prevMonth = (clone $dt)->subMonth()->format('Y-m');
    $nextMonth = (clone $dt)->addMonth()->format('Y-m');

    $baseName = optional($bases->firstWhere('id', $baseId))->name ?? ($baseId ? "拠点#{$baseId}" : '未選択');

    $fmt = function($dt) {
        if(!$dt) return '-';
        try { return Carbon::parse($dt)->format('Y-m-d H:i'); } catch(\Throwable $e) { return (string)$dt; }
    };
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

    {{-- ヘッダー --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2">
                <div class="text-2xl">🔒</div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">勤怠の締め処理</h1>
            </div>
            <p class="mt-1 text-sm text-slate-600">
                月ごとに締めると、管理者による勤怠修正ができなくなります。
            </p>
        </div>

        <div class="flex gap-2">
            @if(Route::has('admin.attendances.index'))
                <a href="{{ route('admin.attendances.index', ['base_id' => $baseId ?: null, 'month' => $month]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 勤怠（月次）へ
                </a>
            @endif
        </div>
    </div>

            {{-- メッセージ --}}
            @if (session('success'))
                <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900">
                    ❌ {{ session('error') }}
                </div>
            @endif

    {{-- フィルタ --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('admin.closings.index') }}"
              class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                <select id="base_id" name="base_id"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    @foreach($bases as $b)
                        <option value="{{ $b->id }}" @selected((int)$b->id === $baseId)>
                            {{ $b->name ?? ('拠点#'.$b->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="month" class="block text-sm font-semibold text-slate-700">月</label>
                <input id="month" name="month" type="month" value="{{ $month }}"
                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                    表示する
                </button>
            </div>
        </form>

        <div class="mt-4 flex items-center justify-between gap-3 text-sm">
            <div class="text-slate-600">
                表示中：<span class="font-bold">{{ $baseName }}</span> / <span class="font-bold">{{ $month }}</span>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.closings.index', ['base_id'=>$baseId,'month'=>$prevMonth]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 前の月
                </a>
                <a href="{{ route('admin.closings.index', ['base_id'=>$baseId,'month'=>$nextMonth]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    次の月 →
                </a>
            </div>
        </div>
    </div>

    {{-- 状態カード --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="font-semibold">この月の状態</div>

                @if($isClosed)
                    <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-extrabold text-rose-700">
                        🔒 締め済み
                    </div>

                    <div class="mt-2 text-sm text-slate-600">
                        締め日時：<span class="font-semibold">{{ $fmt($closing->closed_at ?? null) }}</span>
                        / 締め担当：<span class="font-semibold">ユーザー#{{ $closing->closed_by ?? '-' }}</span>
                    </div>

                    @if($closing?->reopened_at)
                        <div class="mt-1 text-sm text-amber-700">
                            ※ 解除履歴：{{ $fmt($closing->reopened_at) }}（ユーザー#{{ $closing->reopened_by ?? '-' }}）
                        </div>
                    @endif
                @else
                    <div class="mt-2 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700">
                        🔓 未締め
                    </div>
                    <div class="mt-2 text-sm text-slate-600">
                        まだ締めていないため、勤怠修正が可能です。
                    </div>

                    @if($closing?->closed_at)
                        <div class="mt-1 text-sm text-slate-500">
                            （参考）直近の締め日時：{{ $fmt($closing->closed_at) }} / 解除：{{ $fmt($closing->reopened_at ?? null) }}
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex gap-2">
                {{-- 締め --}}
                @if(Route::has('admin.closings.close'))
                    <form method="POST" action="{{ route('admin.closings.close') }}">
                        @csrf
                        <input type="hidden" name="base_id" value="{{ $baseId }}">
                        <input type="hidden" name="year_month" value="{{ $month }}">
                        <button type="submit"
                                class="inline-flex items-center rounded-2xl bg-rose-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-rose-700 active:scale-[0.99]">
                            🔒 締める
                        </button>
                    </form>
                @endif

                {{-- 解除 --}}
                @if(Route::has('admin.closings.open'))
                    <form method="POST" action="{{ route('admin.closings.open') }}">
                        @csrf
                        <input type="hidden" name="base_id" value="{{ $baseId }}">
                        <input type="hidden" name="year_month" value="{{ $month }}">
                        <button type="submit"
                                class="inline-flex items-center rounded-2xl bg-white border border-slate-200 px-5 py-3 text-sm font-extrabold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                            🔓 解除する
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-4 text-xs text-slate-500">
            ※ 締め／解除は shift_attendances の is_locked を一括更新します。
        </div>
    </div>

    {{-- 直近履歴 --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="font-semibold">直近の締め（月）</div>
            <div class="text-xs text-slate-500">最大 12件</div>
        </div>

        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500">
                    <tr>
                        <th class="py-2 pr-4">月</th>
                        <th class="py-2 pr-4">締め日時</th>
                        <th class="py-2 pr-4">解除日時</th>
                        <th class="py-2 pr-4">操作</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $c)
                        <tr>
                            <td class="py-3 pr-4 whitespace-nowrap font-semibold">{{ $c->year_month }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ $fmt($c->closed_at ?? null) }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">{{ $fmt($c->reopened_at ?? null) }}</td>
                            <td class="py-3 pr-4 whitespace-nowrap">
                                <a class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-bold shadow-sm hover:bg-slate-50"
                                   href="{{ route('admin.closings.index', ['base_id'=>$baseId,'month'=>$c->year_month]) }}">
                                    この月を見る
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-600">
                                履歴がありません。
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
