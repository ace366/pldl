<x-app-layout>
    @php
        $fmtBirth = function($v) {
            if (empty($v)) return '';
            try {
                return \Carbon\Carbon::parse($v)->format('Y-m-d');
            } catch (\Exception $e) {
                return (string)$v;
            }
        };

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

        $canView = \App\Services\RolePermissionService::canUser(auth()->user(), 'children_index', 'view');
        $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'children_index', 'update');
    @endphp

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">当日の参加者一覧</h1>
                    <div class="text-xs text-gray-500 mt-1">対象日：{{ $today }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('admin.children.today') }}" class="flex items-center gap-2">
                        <input type="date"
                               name="date"
                               value="{{ $today }}"
                               class="border rounded-md px-2 py-1 text-sm" />
                        <button type="submit"
                                class="px-3 py-1.5 rounded-md text-sm font-semibold bg-gray-800 text-white">
                            表示
                        </button>
                    </form>
                    <a href="{{ route('admin.children.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 underline">
                        児童管理へ戻る
                    </a>
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

            <div class="space-y-4">
                @forelse($grouped as $schoolName => $list)
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 text-sm font-semibold text-gray-700">
                            {{ $schoolName }}（{{ $list->count() }}名）
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-700">
                                    <tr>
                                        <th class="py-2 px-3 border">ID</th>
                                        <th class="py-2 px-3 border">学年</th>
                                        <th class="py-2 px-3 border">氏名</th>
                                        <th class="py-2 px-3 border">ふりがな</th>
                                        <th class="py-2 px-3 border">拠点</th>
                                        <th class="py-2 px-3 border">学校</th>
                                        <th class="py-2 px-3 border">生年月日</th>
                                        <th class="py-2 px-3 border">アレルギー</th>
                                        <th class="py-2 px-3 border js-guardian-col hidden">保護者氏名</th>
                                        <th class="py-2 px-3 border js-guardian-col hidden">メール</th>
                                        <th class="py-2 px-3 border js-guardian-col hidden">電話</th>
                                        <th class="py-2 px-3 border js-guardian-col hidden">続柄</th>
                                        <th class="py-2 px-3 border">未読</th>
                                        <th class="py-2 px-3 border">保護者</th>
                                        <th class="py-2 px-3 border">TEL</th>
                                        <th class="py-2 px-3 border">参加中</th>
                                        <th class="py-2 px-3 border">送迎フラグ</th>
                                        <th class="py-2 px-3 border">在籍</th>
                                        <th class="py-2 px-3 border">編集</th>
                                        <th class="py-2 px-3 border">帰宅</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($list as $idx => $c)
                                        @php
                                            $rowKey = 'row-'.$schoolName.'-'.$idx;
                                            $guardianNames = $c->guardians->map(function($g){
                                                return trim($g->last_name.' '.$g->first_name);
                                            })->filter()->values();
                                            $guardianEmails = $c->guardians->map(fn($g) => $g->email)->filter()->values();
                                            $guardianPhones = $c->guardians->map(fn($g) => $g->phone)->filter()->values();
                                            $guardianRels = $c->guardians->map(function($g){
                                                return $g->pivot->relationship ?? $g->pivot->relation ?? null;
                                            })->filter()->values();

                                            $hasAllergy = (bool)($c->has_allergy ?? false);
                                            $allergyText = (string)($c->allergy_note ?? '');
                                        @endphp
                                        <tr class="hover:bg-gray-50" data-row="{{ $rowKey }}">
                                            <td class="py-3 px-3 border text-gray-700 font-mono">{{ $c->child_code }}</td>
                                            <td class="py-3 px-3 border text-gray-700">{{ $c->grade ?? '—' }}</td>
                                            <td class="py-3 px-3 border text-gray-900 font-semibold">
                                                {{ $c->full_name ?? ($c->last_name.' '.$c->first_name) }}
                                            </td>
                                            <td class="py-3 px-3 border text-gray-700">
                                                {{ $c->last_name_kana ?? '' }} {{ $c->first_name_kana ?? '' }}
                                            </td>
                                            <td class="py-3 px-3 border text-gray-700">
                                                {{ $c->baseMaster?->name ?? '—' }}
                                            </td>
                                            <td class="py-3 px-3 border text-gray-700">
                                                {{ $c->school?->name ?? '—' }}
                                            </td>
                                            <td class="py-3 px-3 border text-gray-700 font-mono">
                                                {{ $fmtBirth($c->birth_date ?? null) }}
                                            </td>
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
                                                @php
                                                    $todayState = (string)($c->today_state ?? 'registered');
                                                @endphp
                                                @if($todayState === 'pickup')
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold js-attending-badge">
                                                        送迎中
                                                    </span>
                                                @elseif($todayState === 'checked_out')
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold js-attending-badge">
                                                        帰宅
                                                    </span>
                                                @elseif($todayState === 'attending')
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold js-attending-badge">
                                                        参加中
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold js-attending-badge">
                                                        登録済
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 border">
                                                @php
                                                    $pickupRequired = (bool)($c->today_pickup_required ?? false);
                                                    $pickupConfirmed = (bool)($c->today_pickup_confirmed ?? false);
                                                @endphp
                                                @if(!$pickupRequired)
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                                        対象外
                                                    </span>
                                                @elseif($pickupConfirmed)
                                                    <div class="flex items-center gap-1">
                                                        <span class="inline-flex px-2 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-semibold">
                                                            済
                                                        </span>
                                                        @if(!empty($c->today_pickup_confirmed_at))
                                                            <span class="text-xs text-gray-500 font-mono">
                                                                {{ $c->today_pickup_confirmed_at }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="inline-flex px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">
                                                        未
                                                    </span>
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
                                            <td class="py-3 px-3 border">
                                                @if($canUpdate)
                                                    <form method="POST" action="{{ route('admin.children.checkout', $c) }}" class="js-checkout" data-row="{{ $rowKey }}">
                                                        @csrf
                                                        <input type="hidden" name="date" value="{{ $today }}">
                                                        <button type="submit"
                                                                class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-semibold
                                                                       {{ ($c->today_state ?? '') === 'checked_out' ? 'bg-gray-200 text-gray-600' : 'bg-rose-600 hover:bg-rose-700 text-white' }}"
                                                                {{ ($c->today_state ?? '') === 'checked_out' ? 'disabled' : '' }}>
                                                            {{ ($c->today_state ?? '') === 'checked_out' ? '帰宅済' : '帰宅' }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="py-6 text-center text-gray-500">
                            本日の参加者がいません。
                        </div>
                    </div>
                @endforelse
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

            const checkoutForms = document.querySelectorAll('.js-checkout');
            checkoutForms.forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const rowKey = form.dataset.row;
                    const row = rowKey ? document.querySelector(`tr[data-row="${rowKey}"]`) : null;
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) btn.disabled = true;

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            body: new FormData(form),
                        });
                        if (!res.ok) throw new Error('failed');
                        if (btn) {
                            btn.textContent = '帰宅済';
                            btn.classList.remove('bg-rose-600', 'hover:bg-rose-700', 'text-white');
                            btn.classList.add('bg-gray-200', 'text-gray-600');
                            btn.disabled = true;
                        }
                        const badge = row ? row.querySelector('.js-attending-badge') : null;
                        if (badge) {
                            badge.textContent = '帰宅';
                            badge.classList.remove('bg-amber-100', 'text-amber-800', 'bg-emerald-100', 'text-emerald-800', 'bg-slate-100', 'text-slate-700');
                            badge.classList.add('bg-gray-100', 'text-gray-700');
                        }
                        form.querySelector('input[name="date"]')?.setAttribute('disabled', 'disabled');
                    } catch (e) {
                        form.submit();
                    }
                });
            });
        })();
    </script>
</x-app-layout>
