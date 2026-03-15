{{-- resources/views/admin/shifts/month.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    // Controllerから渡ってくる想定
    // $month (YYYY-MM)
    // $baseId (int)
    // $bases (Base一覧)
    // $days  = [ ['date'=>'YYYY-MM-DD','count'=>int], ... ]

    $titleMonth = $month ?? now()->format('Y-m');

    // 前月 / 翌月
    try {
        $dt = \Carbon\Carbon::createFromFormat('Y-m', $titleMonth)->startOfMonth();
    } catch (\Throwable $e) {
        $dt = now()->startOfMonth();
    }
    $prevMonth = (clone $dt)->subMonth()->format('Y-m');
    $nextMonth = (clone $dt)->addMonth()->format('Y-m');

    $baseName = optional(($bases ?? collect())->firstWhere('id', (int)($baseId ?? 0)))->name
        ?? (((int)($baseId ?? 0) > 0) ? ('拠点#'.(int)($baseId ?? 0)) : '拠点未選択');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">📅</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    シフト（月表示）
                </h2>
            </div>

            @if(Route::has('admin.shifts.index'))
                <a href="{{ route('admin.shifts.index', ['base_id' => $baseId, 'date' => now()->toDateString()]) }}"
                   class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                    ← 今日の一覧へ
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4">

            {{-- 説明 --}}
            <div class="mb-6">
                <p class="text-sm text-slate-600">
                    「日付」を押すと、その日のシフト一覧へ移動します。
                </p>
            </div>

            {{-- フィルタ --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-5 mb-6">
                <form method="GET" action="{{ route('admin.shifts.month') }}"
                      class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">

                    <div>
                        <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                        <select id="base_id" name="base_id"
                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @foreach(($bases ?? collect()) as $b)
                                <option value="{{ $b->id }}" @selected((int)($baseId ?? 0) === (int)$b->id)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="month" class="block text-sm font-semibold text-slate-700">月</label>
                        <input id="month" name="month" type="month" value="{{ $titleMonth }}"
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
                        表示中：<span class="font-bold">{{ $baseName }}</span> / <span class="font-bold">{{ $titleMonth }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.shifts.month', ['base_id'=>$baseId, 'month'=>$prevMonth]) }}"
                           class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                            ← 前の月
                        </a>
                        <a href="{{ route('admin.shifts.month', ['base_id'=>$baseId, 'month'=>$nextMonth]) }}"
                           class="inline-flex items-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                            次の月 →
                        </a>
                    </div>
                </div>
            </div>

            {{-- 日付リスト --}}
            <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <div class="font-semibold">日付一覧</div>
                    <div class="text-xs text-slate-500 mt-1">件数は「その日のシフト数」です</div>
                </div>

                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach(($days ?? []) as $d)
                            @php
                                $date = (string)($d['date'] ?? '');
                                $cnt  = (int)($d['count'] ?? 0);
                                $dow  = '';
                                try { $dow = \Carbon\Carbon::parse($date)->isoFormat('ddd'); } catch (\Throwable $e) { $dow=''; }
                            @endphp

                            <a href="{{ Route::has('admin.shifts.index') ? route('admin.shifts.index', ['base_id'=>$baseId, 'date'=>$date]) : '#' }}"
                               class="group rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm hover:bg-slate-50 active:scale-[0.99]">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm text-slate-500">日付</div>
                                        <div class="text-lg font-extrabold">
                                            {{ $date }}
                                            <span class="text-sm font-semibold text-slate-500">({{ $dow }})</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-slate-500">シフト数</div>
                                        <div class="text-2xl font-extrabold {{ $cnt > 0 ? 'text-indigo-600' : 'text-slate-400' }}">
                                            {{ $cnt }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-xs text-slate-500 group-hover:text-slate-700">
                                    この日の一覧を見る →
                                </div>
                            </a>
                        @endforeach

                        @if(empty($days))
                            <div class="text-sm text-slate-600">
                                表示できるデータがありません。
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
