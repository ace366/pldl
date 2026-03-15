<x-app-layout>
    @php
        // ====== 現在の検索条件（Controllerから渡されている想定の変数を優先）======
        $q            = $q ?? (string)request('q', '');
        $schoolId     = $schoolId ?? (string)request('school_id', '');
        $enrolledOnly = isset($enrolledOnly) ? (bool)$enrolledOnly : (bool)request('enrolled_only', false);

        // ★追加：学年フィルタ
        $grade        = $grade ?? (string)request('grade', '');

        // ★追加：アレルギー有りフィルタ（1なら絞る）
        $allergyOnly  = isset($allergyOnly) ? (bool)$allergyOnly : ((string)request('allergy_only', '0') === '1');
        $siblingSummaryByChildId = $siblingSummaryByChildId ?? [];

        // ソート
        $sort = (string)request('sort', '');
        $dir  = strtolower((string)request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // クエリを維持したままソートURLを作る
        $sortUrl = function(string $key) use ($dir) {
            $nextDir = ($key === (string)request('sort') && $dir === 'asc') ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir]);
        };

        // “ダブルクリックでソート”のヒント表示
        $sortHint = '（ダブルクリックでソート）';

        // 生年月日表示整形
        $fmtBirth = function($v) {
            if (empty($v)) return '';
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d');
            } catch (\Exception $e) {
                return (string)$v;
            }
        };

        // 電話表示：保存は数字だけ想定（みどり市：携帯070/080/090 or 固定0277）
        $fmtPhone = function($digits) {
            $digits = preg_replace('/\D+/', '', (string)$digits);
            if ($digits === '') return '';
            if (preg_match('/^(070|080|090)\d{8}$/', $digits)) {
                return substr($digits, 0, 3).'-'.substr($digits, 3, 4).'-'.substr($digits, 7, 4);
            }
            if (str_starts_with($digits, '0277')) {
                if (strlen($digits) === 10) return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 4);
                if (strlen($digits) === 11) return substr($digits, 0, 4).'-'.substr($digits, 4, 3).'-'.substr($digits, 7, 4);
            }
            return $digits;
        };

        $canCreate = \App\Services\RolePermissionService::canUser(auth()->user(), 'children_index', 'create');
        $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'children_index', 'update');
        $canView = \App\Services\RolePermissionService::canUser(auth()->user(), 'children_index', 'view');
    @endphp

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800">児童管理</h1>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.children.today') }}"
                       class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-semibold
                              bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200">
                        当日の参加者
                    </a>

                    @if($canCreate)
                    <a href="{{ route('admin.children.create') }}"
                       class="group inline-flex flex-col items-center gap-1">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                     bg-indigo-50 border border-indigo-200 shadow-sm
                                     transition-all duration-200
                                     group-hover:bg-indigo-100 group-hover:-translate-y-0.5 group-hover:shadow
                                     focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                            <img src="{{ asset('images/user100.png') }}"
                                 alt="児童を追加"
                                 class="w-6 h-6 object-contain">
                        </span>

                        <span class="text-sm font-semibold text-gray-900">
                            児童を追加
                        </span>
                    </a>
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ===== 検索エリア ===== --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET"
                      action="{{ route('admin.children.index') }}"
                      class="flex flex-wrap items-end gap-3">

                    {{-- 氏名検索（★縮める） --}}
                    <div class="w-60 min-w-[180px]">
                        <input type="text"
                               name="q"
                               value="{{ $q }}"
                               placeholder="氏名 / ふりがな"
                               class="block w-full rounded-md border-gray-300
                                      focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    {{-- ★追加：学年 --}}
                    <div class="w-36">
                        <select name="grade"
                                class="block w-full rounded-md border-gray-300
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">学年：すべて</option>
                            @for($i=1;$i<=6;$i++)
                                <option value="{{ $i }}" @selected((string)$grade === (string)$i)>{{ $i }}年</option>
                            @endfor
                        </select>
                    </div>

                    {{-- 学校 --}}
                    <div class="w-44">
                        <select name="school_id"
                                class="block w-full rounded-md border-gray-300
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">学校：すべて</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" @selected((string)$schoolId === (string)$s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 在籍のみトグル --}}
                    <input type="hidden" name="enrolled_only" value="0">
                    <label class="inline-flex items-center gap-2 select-none">
                        <input type="checkbox"
                               name="enrolled_only"
                               value="1"
                               @checked($enrolledOnly)
                               class="sr-only peer">

                        <span class="w-11 h-6 bg-gray-200 rounded-full relative transition
                                     peer-checked:bg-indigo-600">
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition
                                         peer-checked:translate-x-5"></span>
                        </span>

                        <span class="text-sm font-semibold text-gray-700">
                            在籍のみ
                        </span>
                    </label>

                    {{-- ★追加：アレルギー有りで絞る --}}
                    <input type="hidden" name="allergy_only" value="0">
                    <label class="inline-flex items-center gap-2 select-none">
                        <input type="checkbox"
                               name="allergy_only"
                               value="1"
                               @checked($allergyOnly)
                               class="sr-only peer">

                        <span class="w-11 h-6 bg-gray-200 rounded-full relative transition
                                     peer-checked:bg-rose-600">
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition
                                         peer-checked:translate-x-5"></span>
                        </span>

                        <span class="text-sm font-semibold text-gray-700">
                            アレルギー有り
                        </span>
                    </label>

                    {{-- ✅ 検索ボタン（虫眼鏡アイコン） --}}
                    <button type="submit"
                            class="inline-flex items-center justify-center w-11 h-10
                                   bg-gray-800 text-white rounded-md hover:bg-gray-900">
                        <span class="sr-only">検索</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m21 21-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                    </button>

                    {{-- リセット --}}
                    <a href="{{ route('admin.children.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 underline">
                        リセット
                    </a>
                </form>

                {{-- ソート説明（控えめ） --}}
                <div class="mt-2 text-xs text-gray-500">
                    ※ 表の見出しを<span class="font-semibold">ダブルクリック</span>すると並び替えできます。
                </div>
            </div>

            {{-- ===== 一覧 ===== --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-5 overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 border-collapse text-center">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-600">
                                {{-- ★ダブルクリックでソート（hoverで気づけるように） --}}
                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="学年 {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('grade') }}'">
                                    学年
                                </th>

                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="ログインID {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('child_code') }}'">
                                    ログインID
                                </th>

                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="氏名 {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('name') }}'">
                                    氏名
                                </th>

                                <th class="py-2 px-3 border">
                                    きょうだい
                                </th>

                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="拠点 {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('base') }}'">
                                    拠点
                                </th>

                                {{-- ★追加：生年月日 --}}
                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="生年月日 {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('birth_date') }}'">
                                    生年月日
                                </th>

                                {{-- ★追加：アレルギー内容 --}}
                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="アレルギー {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('allergy') }}'">
                                    アレルギー
                                </th>

                                {{-- ✅ 既存 --}}
                                <th class="py-2 px-3 border js-guardian-col hidden">保護者</th>
                                <th class="py-2 px-3 border js-guardian-col hidden">メール</th>
                                <th class="py-2 px-3 border js-guardian-col hidden">電話</th>
                                <th class="py-2 px-3 border js-guardian-col hidden">続柄</th>
                                <th class="py-2 px-3 border w-24">連絡</th>
                                <th class="py-2 px-3 border w-24">保護者情報</th>
                                <th class="py-2 px-3 border w-24">TEL票</th>
                                <th class="py-2 px-3 border cursor-pointer select-none"
                                    title="状態 {{ $sortHint }}"
                                    ondblclick="location.href='{{ $sortUrl('status') }}'">
                                    状態
                                </th>

                                <th class="py-2 px-3 border w-28">操作</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($children as $c)
                                @php
                                    $gs = $c->guardians ?? collect();

                                    $guardianNames  = $gs->map(fn($g) => trim(($g->last_name ?? '').' '.($g->first_name ?? '')))->filter()->values();
                                    $guardianEmails = $gs->pluck('email')->filter()->values();
                                    $guardianPhones = $gs->pluck('phone')->filter()->values();
                                    $guardianRels   = $gs->map(fn($g) => $g->pivot?->relationship)->filter()->values();

                                    // ★追加：アレルギー表示（無ければ空欄 / あれば赤字）
                                    $allergyText = (string)($c->allergy_note ?? '');
                                    $hasAllergy  = (int)($c->has_allergy ?? 0) === 1;
                                    $siblingInfo = $siblingSummaryByChildId[(int)$c->id] ?? ['count' => 0, 'names' => [], 'code' => null];

                                    $rowKey = 'child-'.$c->id;
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 border font-medium text-gray-800">{{ $c->grade }}年</td>

                                    <td class="py-3 px-3 border text-gray-700 font-mono">
                                        {{ $c->child_code ?? '—' }}
                                    </td>

                                    <td class="py-3 px-3 border text-gray-900">
                                        <a href="{{ route('admin.children.tel.index', $c) }}"
                                           class="block text-blue-700 hover:text-blue-900 font-semibold">
                                            <div class="text-xs text-gray-600">
                                                {{ trim(($c->last_name_kana ?? '').' '.($c->first_name_kana ?? '')) }}
                                            </div>
                                            <div class="text-sm">
                                                {{ $c->last_name }} {{ $c->first_name }}
                                            </div>
                                        </a>
                                    </td>

                                    <td class="py-3 px-3 border text-left align-top">
                                        @if(($siblingInfo['count'] ?? 0) > 0)
                                            <div class="text-xs font-semibold text-emerald-700">
                                                きょうだい {{ (int)$siblingInfo['count'] }}名
                                            </div>
                                            <div class="mt-1 space-y-0.5 text-xs text-gray-700 leading-5">
                                                @foreach(($siblingInfo['names'] ?? []) as $siblingName)
                                                    <div>{{ $siblingName }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700">
                                        {{ $c->baseMaster?->name ?? '—' }}
                                    </td>

                                    {{-- ★追加：生年月日 --}}
                                    <td class="py-3 px-3 border text-gray-700 font-mono">
                                        {{ $fmtBirth($c->birth_date ?? null) }}
                                    </td>

                                    {{-- ★追加：アレルギー内容（無ければ空欄 / あれば赤字） --}}
                                    <td class="py-3 px-3 border text-left">
                                        @if($hasAllergy && $allergyText !== '')
                                            <span class="text-rose-700 font-semibold">
                                                {{ $allergyText }}
                                            </span>
                                        @elseif($hasAllergy && $allergyText === '')
                                            <span class="text-rose-700 font-semibold">
                                                有（内容未入力）
                                            </span>
                                        @else
                                            {{-- 無い場合は空欄（指示通り） --}}
                                            <span class="text-gray-400"></span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700 js-guardian-col hidden">
                                        <div class="js-guardian-detail hidden" data-row="{{ $rowKey }}">
                                            @if($guardianNames->isNotEmpty())
                                                {!! $guardianNames->map(fn($v) => e($v))->join('<br>') !!}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700 js-guardian-col hidden">
                                        <div class="js-guardian-detail hidden" data-row="{{ $rowKey }}">
                                            @if($guardianEmails->isNotEmpty())
                                                {!! $guardianEmails->map(fn($v) => e($v))->join('<br>') !!}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700 js-guardian-col hidden">
                                        <div class="js-guardian-detail hidden" data-row="{{ $rowKey }}">
                                            @if($guardianPhones->isNotEmpty())
                                                {!! $guardianPhones->map(fn($v) => e($fmtPhone($v)))->join('<br>') !!}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700 js-guardian-col hidden">
                                        <div class="js-guardian-detail hidden" data-row="{{ $rowKey }}">
                                            @if($guardianRels->isNotEmpty())
                                                {!! $guardianRels->map(fn($v) => e($v))->join('<br>') !!}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-3 px-3 border">
                                        @php $unread = (int)($c->unread_message_count ?? 0); @endphp
                                        <a href="{{ route('admin.children.messages.index', $c) }}"
                                           class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-semibold
                                                  {{ $unread > 0 ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200' }}">
                                            {{ $unread > 0 ? '未読' : '既読' }}
                                            @if($unread > 0)
                                                <span class="ml-1">{{ $unread }}</span>
                                            @endif
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 border">
                                        <button type="button"
                                                class="js-guardian-toggle inline-flex items-center justify-center px-3 py-1 rounded text-xs font-semibold
                                                       bg-slate-100 hover:bg-slate-200 text-slate-700"
                                                data-row="{{ $rowKey }}"
                                                aria-expanded="false">
                                            表示
                                        </button>
                                    </td>
                                    <td class="py-3 px-3 border">
                                        @if($canView)
                                            <a href="{{ route('admin.children.tel.index', $c) }}"
                                               class="inline-flex items-center justify-center px-3 py-1 rounded
                                                      bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold
                                                      border border-indigo-200">
                                                TEL票
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border">
                                        @if($c->status === 'enrolled')
                                            <span class="inline-flex px-2 py-1 rounded bg-green-100 text-green-800 text-xs">
                                                在籍
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                                退会
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border">
                                        @if($canUpdate)
                                            <a href="{{ route('admin.children.edit', $c) }}"
                                               class="inline-flex items-center justify-center px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs">
                                                編集
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="py-6 border text-center text-gray-500">
                                        児童がまだ登録されていません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $children->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const buttons = document.querySelectorAll('.js-guardian-toggle');
            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const key = btn.dataset.row;
                    if (!key) return;
                    const targets = document.querySelectorAll(`.js-guardian-detail[data-row="${key}"]`);
                    if (!targets.length) return;

                    const isHidden = targets[0].classList.contains('hidden');
                    targets.forEach((t) => t.classList.toggle('hidden', !isHidden));
                    document.querySelectorAll('.js-guardian-col').forEach((th) => {
                        th.classList.toggle('hidden', !isHidden);
                    });
                    btn.textContent = isHidden ? '隠す' : '表示';
                    btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                });
            });
        })();
    </script>
</x-app-layout>
