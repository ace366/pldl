<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-sky-50 py-6">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            {{-- ヘッダー --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs text-slate-500">勤怠管理 / 管理者</div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            シフト追加
                        </h1>
                        <div class="mt-1 text-sm text-slate-600">
                            日付・拠点・時間・担当者を入力して保存します。
                        </div>
                    </div>

                    <a href="{{ route('admin.shifts.index', request()->query()) }}"
                       class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold
                              bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                        ← 戻る
                    </a>
                </div>
            </div>

            {{-- バリデーションエラー --}}
            @if ($errors->any())
                <div class="rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 text-sm">
                    <div class="font-extrabold mb-1">入力内容を確認してください</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $role = Auth::user()->role ?? '';
                $isAdmin = ($role === 'admin');

                $bases      = $bases ?? collect();
                $staffUsers = $staffUsers ?? collect();
                $date       = $date ?? request()->query('date', now()->toDateString());
                $baseId     = $baseId ?? (int)request()->query('base_id', 0);

                // 旧入力
                $vDate  = old('shift_date', $date);
                $vBase  = (int)old('base_id', $baseId);
                $vStart = old('start_time', request()->query('start_time', '14:00'));
                $vEnd   = old('end_time', request()->query('end_time', '18:00'));
                $vNote  = old('note', '');
                $vUserId = (int)old('user_id', Auth::id());
            @endphp

            {{-- ✅ 拠点を選ぶと担当者が出る（GETで再読込） --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                <form method="GET" action="{{ route('admin.shifts.create') }}" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">日付（担当者候補には影響しません）</label>
                            <input type="date" name="date" value="{{ $vDate }}"
                                   class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">拠点（先に選択）</label>
                            <select name="base_id" required
                                    class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200"
                                    onchange="this.form.submit()">
                                <option value="">選択してください</option>
                                @foreach($bases as $b)
                                    <option value="{{ (int)$b->id }}" @selected((int)$b->id === (int)$vBase)>
                                        {{ $b->name ?? ('拠点#'.$b->id) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-[11px] text-slate-500">
                                ※ 拠点を選ぶと、その拠点の職員一覧を読み込みます（自動更新）
                            </div>
                        </div>
                    </div>

                    {{-- ここで GET しても入力が消えないよう、時間なども引き継ぐ --}}
                    <input type="hidden" name="start_time" value="{{ $vStart }}">
                    <input type="hidden" name="end_time" value="{{ $vEnd }}">
                    <input type="hidden" name="note" value="{{ $vNote }}">
                    <input type="hidden" name="user_id" value="{{ (int)$vUserId }}">
                </form>
            </div>

            {{-- フォーム（POST） --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('admin.shifts.store') }}" class="space-y-5"
                      onsubmit="return confirm('この内容でシフトを登録します。よろしいですか？');">
                    @csrf

                    {{-- ✅ 拠点（POSTにも必須） --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">拠点</label>
                        <select name="base_id" required
                                class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            <option value="">選択してください</option>
                            @foreach($bases as $b)
                                <option value="{{ (int)$b->id }}" @selected((int)$b->id === (int)$vBase)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ 担当者 --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">担当者</label>

                        @if($isAdmin)
                            {{-- ✅ 拠点所属（staff_bases）が無い時の案内 --}}
                            @if(($staffUsers?->count() ?? 0) === 0)
                                <div class="rounded-2xl bg-amber-50 border border-amber-100 text-amber-900 px-4 py-3 text-sm">
                                    <div class="font-extrabold mb-1">担当者が表示できません</div>
                                    <div class="text-xs text-amber-800">
                                        この拠点に所属する職員が未登録です。先に「拠点所属」を登録してください。
                                    </div>

                                    @if(\Illuminate\Support\Facades\Route::has('admin.staff_bases.create'))
                                        <a href="{{ route('admin.staff_bases.create', ['base_id' => $vBase ?: $baseId]) }}"
                                        class="mt-3 inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-extrabold
                                                bg-amber-600 text-white hover:bg-amber-700 transition">
                                            ➕ 拠点所属を登録する
                                        </a>
                                    @endif
                                </div>
                            @else
                                <select name="user_id" required
                                        class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                                    <option value="">
                                        {{ $vBase ? '選択してください' : '先に拠点を選んでください' }}
                                    </option>

                                    @foreach($staffUsers as $u)
                                        @php
                                            $nm = ($u->name ?? '');
                                            if ($nm === '') {
                                                $nm = trim(($u->last_name ?? '').' '.($u->first_name ?? ''));
                                            }
                                            $nm = $nm !== '' ? $nm : ('User#'.$u->id);
                                        @endphp
                                        <option value="{{ (int)$u->id }}" @selected((int)$u->id === (int)$vUserId)>
                                            {{ $nm }}
                                        </option>
                                    @endforeach
                                </select>

                                @if(!$vBase)
                                    <div class="mt-1 text-xs text-rose-600 font-semibold">
                                        ※ 先に「拠点」を選択すると、担当者候補が表示されます。
                                    </div>
                                @else
                                    <div class="mt-1 text-xs text-slate-500">
                                        ※ admin のみ担当者を選択できます（拠点所属の職員のみ表示）
                                    </div>
                                @endif
                            @endif
                        @else
                            <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 font-semibold">
                                {{ Auth::user()->name ?? 'ユーザー' }}（あなた）
                            </div>
                            <input type="hidden" name="user_id" value="{{ (int)$vUserId }}">
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">日付</label>
                        <input type="date" name="shift_date" value="{{ $vDate }}" required
                               class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">開始</label>
                            <input type="time" name="start_time" value="{{ $vStart }}" required
                                   class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">終了</label>
                            <input type="time" name="end_time" value="{{ $vEnd }}" required
                                   class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">メモ（任意）</label>
                        <textarea name="note" rows="3"
                                  class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200"
                                  placeholder="例：午前は受付対応、午後は送迎など">{{ $vNote }}</textarea>
                    </div>

                    <div class="pt-2 flex gap-2">
                        <button type="submit"
                                @disabled($isAdmin && (($staffUsers?->count() ?? 0) === 0))
                                class="flex-1 rounded-2xl px-4 py-4 text-base font-extrabold
                                       bg-indigo-600 text-white hover:bg-indigo-700 active:scale-[0.99] transition shadow
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            ＋ 登録
                        </button>

                        <a href="{{ route('admin.shifts.index', ['date' => $vDate, 'base_id' => $vBase]) }}"
                           class="rounded-2xl px-4 py-4 text-base font-semibold
                                  bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                            キャンセル
                        </a>
                    </div>

                </form>
            </div>

            <div class="text-center text-xs text-slate-400 py-4">
                PLDL 勤怠・シフト管理
            </div>
        </div>
    </div>
</x-app-layout>
