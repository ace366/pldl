<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-sky-50 py-6">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs text-slate-500">勤怠管理 / 管理者</div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            シフト追加
                        </h1>
                        <div class="mt-1 text-sm text-slate-600">
                            単日登録を維持しつつ、期間指定の一括登録にも対応します。
                        </div>
                    </div>

                    <a href="{{ route('admin.shifts.index', request()->query()) }}"
                       class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold
                              bg-slate-100 text-slate-700 hover:bg-slate-200 active:scale-[0.99] transition">
                        ← 戻る
                    </a>
                </div>
            </div>

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

                $entryMode = old('entry_mode', request()->query('entry_mode', 'single'));
                $vDate = old('shift_date', request()->query('shift_date', $date));
                $vBase = (int)old('base_id', $baseId);
                $vStart = old('start_time', request()->query('start_time', '14:00'));
                $vEnd = old('end_time', request()->query('end_time', '18:00'));
                $vNote = old('note', request()->query('note', ''));
                $vUserId = (int)old('user_id', request()->query('user_id', Auth::id()));

                $bulkStartDate = old('bulk_start_date', request()->query('bulk_start_date', $date));
                $bulkEndDate = old('bulk_end_date', request()->query('bulk_end_date', $date));
                $bulkPattern = old('bulk_pattern', request()->query('bulk_pattern', 'daily'));
                $bulkWeekdays = collect((array)old('bulk_weekdays', request()->query('bulk_weekdays', [1, 2, 3, 4, 5])))
                    ->map(fn ($day) => (int)$day)
                    ->filter(fn ($day) => $day >= 1 && $day <= 5)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $confirmOverwrite = old('confirm_overwrite', '0') === '1';

                $weekdayLabels = [
                    1 => '月',
                    2 => '火',
                    3 => '水',
                    4 => '木',
                    5 => '金',
                ];

                $bulkPreview = session('shift_bulk_preview', []);
                $duplicateDates = $bulkPreview['duplicate_dates'] ?? [];
                $blockedItems = $bulkPreview['blocked'] ?? [];
                $newDates = $bulkPreview['new_dates'] ?? [];
                $excludedWeekends = $bulkPreview['excluded_weekends'] ?? [];
                $excludedHolidays = $bulkPreview['excluded_holidays'] ?? [];
                $excludedByPattern = $bulkPreview['excluded_by_pattern'] ?? [];
            @endphp

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                <form method="GET" action="{{ route('admin.shifts.create') }}" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">基準日</label>
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

                    <input type="hidden" name="entry_mode" value="{{ $entryMode }}">
                    <input type="hidden" name="shift_date" value="{{ $vDate }}">
                    <input type="hidden" name="start_time" value="{{ $vStart }}">
                    <input type="hidden" name="end_time" value="{{ $vEnd }}">
                    <input type="hidden" name="note" value="{{ $vNote }}">
                    <input type="hidden" name="user_id" value="{{ (int)$vUserId }}">
                    <input type="hidden" name="bulk_start_date" value="{{ $bulkStartDate }}">
                    <input type="hidden" name="bulk_end_date" value="{{ $bulkEndDate }}">
                    <input type="hidden" name="bulk_pattern" value="{{ $bulkPattern }}">
                    @foreach($bulkWeekdays as $weekday)
                        <input type="hidden" name="bulk_weekdays[]" value="{{ $weekday }}">
                    @endforeach
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('admin.shifts.store') }}" class="space-y-5"
                      onsubmit="return confirm('この内容でシフトを登録します。よろしいですか？');">
                    @csrf

                    <div class="space-y-3">
                        <div class="text-xs font-bold text-slate-600">登録モード</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="rounded-2xl border px-4 py-4 cursor-pointer transition
                                          {{ $entryMode === 'single' ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                                <input type="radio" name="entry_mode" value="single" class="sr-only" @checked($entryMode === 'single')>
                                <div class="font-extrabold text-slate-900">単日登録</div>
                                <div class="mt-1 text-sm text-slate-600">1日分だけ登録します。</div>
                            </label>
                            <label class="rounded-2xl border px-4 py-4 cursor-pointer transition
                                          {{ $entryMode === 'bulk' ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                                <input type="radio" name="entry_mode" value="bulk" class="sr-only" @checked($entryMode === 'bulk')>
                                <div class="font-extrabold text-slate-900">一括登録</div>
                                <div class="mt-1 text-sm text-slate-600">期間・曜日を指定してまとめて登録します。</div>
                            </label>
                        </div>
                    </div>

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

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">担当者</label>

                        @if($isAdmin)
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

                                <div class="mt-1 text-xs text-slate-500">
                                    ※ admin のみ担当者を選択できます（拠点所属の職員のみ表示）
                                </div>
                            @endif
                        @else
                            <div class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 font-semibold">
                                {{ Auth::user()->name ?? 'ユーザー' }}（あなた）
                            </div>
                            <input type="hidden" name="user_id" value="{{ (int)$vUserId }}">
                        @endif
                    </div>

                    <div data-entry-section="single" @class(['space-y-5', 'hidden' => $entryMode !== 'single'])>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">日付</label>
                            <input type="date" name="shift_date" value="{{ $vDate }}"
                                   class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div data-entry-section="bulk" @class(['space-y-5', 'hidden' => $entryMode !== 'bulk'])>
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-4 text-sm text-slate-700">
                            <div class="font-extrabold text-indigo-700">一括登録のルール</div>
                            <ul class="mt-2 list-disc list-inside space-y-1 text-xs sm:text-sm">
                                <li>対象は1人ずつです</li>
                                <li>期間は「開始日〜終了日」で指定します</li>
                                <li>土日と祝日は自動で除外します</li>
                                <li>重複日がある場合は、確認後に上書きします</li>
                            </ul>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">開始日</label>
                                <input type="date" name="bulk_start_date" value="{{ $bulkStartDate }}"
                                       class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">終了日</label>
                                <input type="date" name="bulk_end_date" value="{{ $bulkEndDate }}"
                                       class="w-full rounded-2xl border-slate-200 focus:border-indigo-400 focus:ring-indigo-200">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="text-xs font-bold text-slate-600">登録方式</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="rounded-2xl border px-4 py-4 cursor-pointer transition
                                              {{ $bulkPattern === 'daily' ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white' }}">
                                    <input type="radio" name="bulk_pattern" value="daily" class="sr-only" @checked($bulkPattern === 'daily')>
                                    <div class="font-extrabold text-slate-900">毎日登録</div>
                                    <div class="mt-1 text-sm text-slate-600">平日を毎日対象にします。</div>
                                </label>
                                <label class="rounded-2xl border px-4 py-4 cursor-pointer transition
                                              {{ $bulkPattern === 'weekday' ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white' }}">
                                    <input type="radio" name="bulk_pattern" value="weekday" class="sr-only" @checked($bulkPattern === 'weekday')>
                                    <div class="font-extrabold text-slate-900">曜日指定登録</div>
                                    <div class="mt-1 text-sm text-slate-600">月〜金から曜日を選びます。</div>
                                </label>
                            </div>
                        </div>

                        <div data-weekday-selector @class(['space-y-3', 'hidden' => $bulkPattern !== 'weekday'])>
                            <div class="text-xs font-bold text-slate-600">対象曜日</div>
                            <div class="grid grid-cols-5 gap-2">
                                @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                    <label class="rounded-2xl border px-3 py-3 text-center cursor-pointer transition
                                                  {{ in_array($weekdayValue, $bulkWeekdays, true) ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-700' }}">
                                        <input type="checkbox" name="bulk_weekdays[]" value="{{ $weekdayValue }}" class="sr-only"
                                               @checked(in_array($weekdayValue, $bulkWeekdays, true))>
                                        <div class="font-extrabold">{{ $weekdayLabel }}</div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @if(!empty($bulkPreview))
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 space-y-3">
                                <div>
                                    <div class="font-extrabold text-slate-900">一括登録の確認</div>
                                    <div class="text-xs text-slate-500">
                                        対象候補 {{ count($bulkPreview['target_dates'] ?? []) }} 日 /
                                        新規 {{ count($newDates) }} 日 /
                                        重複 {{ count($duplicateDates) }} 日
                                    </div>
                                </div>

                                @if(!empty($duplicateDates))
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        <div class="font-extrabold">重複する日があります</div>
                                        <div class="mt-1 text-xs">
                                            {{ implode(' / ', $duplicateDates) }}
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($blockedItems))
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                                        <div class="font-extrabold">上書きできない日があります</div>
                                        <ul class="mt-2 space-y-1 text-xs">
                                            @foreach($blockedItems as $blocked)
                                                <li>{{ $blocked['date'] ?? '' }}: {{ $blocked['reason'] ?? '' }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div class="rounded-2xl bg-white border border-slate-200 px-3 py-3">
                                        <div class="font-bold text-slate-700">新規作成予定</div>
                                        <div class="mt-1 text-slate-500">{{ count($newDates) }} 日</div>
                                    </div>
                                    <div class="rounded-2xl bg-white border border-slate-200 px-3 py-3">
                                        <div class="font-bold text-slate-700">土日除外</div>
                                        <div class="mt-1 text-slate-500">{{ count($excludedWeekends) }} 日</div>
                                    </div>
                                    <div class="rounded-2xl bg-white border border-slate-200 px-3 py-3">
                                        <div class="font-bold text-slate-700">祝日除外</div>
                                        <div class="mt-1 text-slate-500">{{ count($excludedHolidays) }} 日</div>
                                    </div>
                                </div>

                                @if(!empty($excludedHolidays))
                                    <details class="rounded-2xl bg-white border border-slate-200 px-4 py-3">
                                        <summary class="cursor-pointer font-semibold text-sm text-slate-800">祝日として除外した日を見る</summary>
                                        <ul class="mt-2 space-y-1 text-xs text-slate-600">
                                            @foreach($excludedHolidays as $holiday)
                                                <li>{{ $holiday['date'] ?? '' }}: {{ $holiday['name'] ?? '' }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif

                                @if(!empty($excludedByPattern))
                                    <details class="rounded-2xl bg-white border border-slate-200 px-4 py-3">
                                        <summary class="cursor-pointer font-semibold text-sm text-slate-800">曜日指定で除外した日を見る</summary>
                                        <div class="mt-2 text-xs text-slate-600">
                                            {{ implode(' / ', $excludedByPattern) }}
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endif

                        @if(!empty($duplicateDates) && empty($blockedItems))
                            <label class="inline-flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <input type="checkbox" name="confirm_overwrite" value="1"
                                       class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600"
                                       @checked($confirmOverwrite)>
                                <span>
                                    <span class="font-extrabold block">重複日を上書きして登録する</span>
                                    <span class="text-xs">既存シフトがある日を、今回の時間・メモで更新します。</span>
                                </span>
                            </label>
                        @endif
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
                            <span data-submit-label>{{ $entryMode === 'bulk' ? '＋ 一括登録' : '＋ 登録' }}</span>
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

    <script>
        (function () {
            const modeInputs = Array.from(document.querySelectorAll('input[name="entry_mode"]'));
            const patternInputs = Array.from(document.querySelectorAll('input[name="bulk_pattern"]'));
            const singleSection = document.querySelector('[data-entry-section="single"]');
            const bulkSection = document.querySelector('[data-entry-section="bulk"]');
            const weekdaySelector = document.querySelector('[data-weekday-selector]');
            const submitLabel = document.querySelector('[data-submit-label]');

            const currentMode = () => modeInputs.find((input) => input.checked)?.value || 'single';
            const currentPattern = () => patternInputs.find((input) => input.checked)?.value || 'daily';

            const syncMode = () => {
                const mode = currentMode();
                if (singleSection) singleSection.classList.toggle('hidden', mode !== 'single');
                if (bulkSection) bulkSection.classList.toggle('hidden', mode !== 'bulk');
                if (submitLabel) submitLabel.textContent = mode === 'bulk' ? '＋ 一括登録' : '＋ 登録';
                syncPattern();
            };

            const syncPattern = () => {
                if (!weekdaySelector) return;
                const mode = currentMode();
                const pattern = currentPattern();
                weekdaySelector.classList.toggle('hidden', !(mode === 'bulk' && pattern === 'weekday'));
            };

            modeInputs.forEach((input) => input.addEventListener('change', syncMode));
            patternInputs.forEach((input) => input.addEventListener('change', syncPattern));

            syncMode();
        })();
    </script>
</x-app-layout>
