<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-sky-50 py-6">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs text-slate-500">勤怠管理 / 管理者</div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            職員所属（拠点）登録
                        </h1>
                        <div class="mt-1 text-sm text-slate-600">
                            シフトで担当者が出るように、職員を拠点に所属させます。
                        </div>
                    </div>

                    <a href="{{ route('admin.shifts.create') }}"
                       class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold
                              bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                        ← シフト追加へ
                    </a>
                </div>
            </div>

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

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('admin.staff_bases.store') }}"
                      class="space-y-5"
                      onsubmit="return confirm('この内容で所属を登録します。よろしいですか？');">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">職員</label>
                        <select name="user_id" required
                                class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            <option value="">選択してください</option>
                            @foreach($users as $u)
                                @php
                                    $nm = ($u->name ?? '');
                                    if ($nm === '') $nm = trim(($u->last_name ?? '').' '.($u->first_name ?? ''));
                                    $nm = $nm !== '' ? $nm : ('User#'.$u->id);
                                @endphp
                                <option value="{{ (int)$u->id }}" @selected((int)old('user_id') === (int)$u->id)>
                                    {{ $nm }}（{{ $u->role }} / ID:{{ $u->id }}）
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">拠点</label>
                        <select name="base_id" required
                                class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            <option value="">選択してください</option>
                            @foreach($bases as $b)
                                <option value="{{ (int)$b->id }}" @selected((int)old('base_id') === (int)$b->id)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_primary" type="checkbox" name="is_primary" value="1"
                               class="rounded border-slate-300"
                               @checked(old('is_primary'))>
                        <label for="is_primary" class="text-sm text-slate-700 font-semibold">
                            主所属にする（その職員の主所属は1つだけにします）
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full rounded-2xl px-4 py-4 text-base font-extrabold
                                   bg-indigo-600 text-white hover:bg-indigo-700 active:scale-[0.99] transition shadow">
                        ＋ 所属を登録
                    </button>
                </form>
            </div>

            <div class="text-center text-xs text-slate-400 py-4">
                PLDL 勤怠・シフト管理
            </div>
        </div>
    </div>
</x-app-layout>
