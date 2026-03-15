<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-sky-50 via-white to-indigo-50 py-6">
        <div class="max-w-6xl mx-auto px-4 space-y-6">

            {{-- ヘッダー --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs text-slate-500">勤怠管理 / 管理者</div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            シフト（日別）
                        </h1>
                        <div class="mt-1 text-sm text-slate-600">
                            日付を選んでシフトを確認・追加できます。
                        </div>
                    </div>

                    @php
                        $canCreate = \App\Services\RolePermissionService::canUser(auth()->user(), 'shift_day', 'create');
                        $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'shift_day', 'update');
                        $canDelete = \App\Services\RolePermissionService::canUser(auth()->user(), 'shift_day', 'delete');
                    @endphp

                    <div class="flex flex-col sm:flex-row gap-2">
                        @if(\Illuminate\Support\Facades\Route::has('admin.shifts.month'))
                            <a href="{{ route('admin.shifts.month') }}"
                               class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold
                                      bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                                月表示
                            </a>
                        @endif

                        @if($canCreate && \Illuminate\Support\Facades\Route::has('admin.shifts.create'))
                            <a href="{{ route('admin.shifts.create', request()->query()) }}"
                               class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-extrabold
                                      bg-indigo-600 text-white hover:bg-indigo-700 active:scale-[0.99] transition shadow">
                                ＋ 追加
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 成功/エラー --}}
            @if(session('success'))
                <div class="rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 px-4 py-3 text-sm font-semibold">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 text-sm font-semibold">
                    ❌ {{ session('error') }}
                </div>
            @endif

            @php
                // Controller 側の変数名に多少差があっても壊れないように吸収
                $date   = $date   ?? request()->query('date', now()->toDateString());
                $baseId = $baseId ?? (int)request()->query('base_id', 0);
                $bases  = $bases  ?? collect();

                // 一覧データ（想定：$shifts or $rows）
                $rows = $shifts ?? ($rows ?? collect());
            @endphp

            {{-- フィルタ --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
                <form method="GET" action="{{ route('admin.shifts.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">日付</label>
                        <input type="date" name="date" value="{{ $date }}"
                               class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">拠点</label>
                        <select name="base_id"
                                class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            <option value="0">選択してください</option>
                            @foreach($bases as $b)
                                <option value="{{ (int)$b->id }}" @selected((int)$b->id === (int)$baseId)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 rounded-2xl px-4 py-3 text-sm font-extrabold
                                       bg-slate-900 text-white hover:bg-slate-800 active:scale-[0.99] transition">
                            表示
                        </button>

                        <a href="{{ route('admin.shifts.index') }}"
                           class="rounded-2xl px-4 py-3 text-sm font-semibold
                                  bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                            リセット
                        </a>
                    </div>
                </form>
            </div>

            {{-- 一覧 --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-bold text-slate-800">
                            {{ $date }} のシフト
                        </div>
                        <div class="text-xs text-slate-500">
                            件数：{{ is_countable($rows) ? count($rows) : ($rows?->count() ?? 0) }}
                        </div>
                    </div>
                </div>

                @if(($rows?->count() ?? 0) === 0)
                    <div class="p-10 text-center">
                        <div class="text-4xl mb-2">🗓️</div>
                        <div class="text-lg font-extrabold text-slate-800">シフトがありません</div>
                        @if($canCreate)
                            <div class="text-sm text-slate-600 mt-2">
                                右上の「＋追加」から作成できます。
                            </div>
                        @endif
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($rows as $shift)
                            @php
                                $start = $shift->start_time ?? '';
                                $end   = $shift->end_time ?? '';
                                $label = trim(($start && $end) ? "{$start}〜{$end}" : ($shift->label ?? ''));
                                $baseName = $shift->base->name ?? ($shift->base_name ?? '');

                                // ✅ 担当者名（name優先→姓+名→User#）
                                $u = $shift->user ?? null;
                                $userName =
                                    ($u?->name ?? '') !== '' ? $u->name
                                    : trim(($u?->last_name ?? '').' '.($u?->first_name ?? ''));
                                $userName = $userName !== '' ? $userName : ('User#'.($shift->user_id ?? ''));

                                // ✅ note（保存先は note）
                                $note = $shift->note ?? null;
                            @endphp

                            <div class="p-5 flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="text-lg font-extrabold text-slate-900">
                                        {{ $label !== '' ? $label : '（時間未設定）' }}
                                    </div>

                                    <div class="text-sm text-slate-700 font-semibold">
                                        👤 {{ $userName }}
                                    </div>

                                    <div class="text-sm text-slate-600">
                                        📍 {{ $baseName !== '' ? $baseName : '拠点未設定' }}
                                    </div>

                                    @if(!empty($note))
                                        <div class="text-xs text-slate-500">
                                            📝 {{ $note }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col sm:flex-row gap-2">
                                    @if($canUpdate && \Illuminate\Support\Facades\Route::has('admin.shifts.edit'))
                                        <a href="{{ route('admin.shifts.edit', $shift->id) }}"
                                           class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold
                                                  bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                                            編集
                                        </a>
                                    @endif

                                    @if($canDelete && \Illuminate\Support\Facades\Route::has('admin.shifts.destroy'))
                                        <form method="POST" action="{{ route('admin.shifts.destroy', $shift->id) }}"
                                              onsubmit="return confirm('このシフトを削除します。よろしいですか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-extrabold
                                                           bg-rose-600 text-white hover:bg-rose-700 active:scale-[0.99] transition">
                                                削除
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="text-center text-xs text-slate-400 py-4">
                PLDL 勤怠・シフト管理
            </div>
        </div>
    </div>
</x-app-layout>
