{{-- resources/views/family/availability.blade.php --}}
<x-app-layout>
    @php
        $selectedDates = collect($intents)->keys()->flip(); // "YYYY-MM-DD" => true
        $today = now()->toDateString();

        $prevStart = $gridStart->copy()->subDays(28)->toDateString();
        $nextStart = $gridStart->copy()->addDays(28)->toDateString();

        $rangeLabel = $gridStart->format('n/j') . ' 〜 ' . $gridEnd->format('n/j');
        $weekdays = ['日','月','火','水','木','金','土'];

        // 「その月」＝表示グリッド開始日($gridStart)の月
        $monthLabel = $gridStart->format('Y年n月');
        $monthStart = $gridStart->copy()->startOfMonth()->toDateString();
        $monthEnd   = $gridStart->copy()->endOfMonth()->toDateString();
    @endphp

    <style>
        /* =========================
           PC（現状維持）
        ========================= */
        .calWrap {
            border-radius: 24px;
            border: 1px solid rgba(99,102,241,.18);
            padding: 18px;
            background: linear-gradient(135deg, #eef2ff 0%, #eff6ff 45%, #fdf2f8 100%);
        }
        .calHead {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .calTitle { display: flex; gap: 10px; align-items: center; }
        .calBadge {
            width: 40px; height: 40px;
            border-radius: 16px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(15,23,42,.08);
            font-size: 18px;
        }
        .calRange { font-size: 12px; color: #475569; margin-top: 2px; }
        .calBtns { display: flex; gap: 8px; flex-wrap: wrap; }
        .calBtn {
            background: #fff;
            border: 0;
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 800;
            font-size: 13px;
            color: #334155;
            box-shadow: 0 2px 10px rgba(15,23,42,.08);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .calBtn:hover { box-shadow: 0 6px 18px rgba(15,23,42,.12); }

        .dowRow {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
            margin-top: 14px;
            text-align: center;
            font-weight: 900;
            font-size: 12px;
            color: #475569;
        }
        .dowSun { color: #db2777; }
        .dowSat { color: #4f46e5; }

        .calGrid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .dayCell {
            border-radius: 18px;
            border: 1px solid rgba(148,163,184,.35);
            background: rgba(255,255,255,.85);
            padding: 10px;
            aspect-ratio: 1 / 1;
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: transform .08s ease, box-shadow .12s ease, background .12s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            min-width: 0;
            overflow: hidden;
        }
        .dayCell:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15,23,42,.12);
            background: rgba(255,255,255,.95);
        }
        .dayCell:disabled {
            opacity: .45;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
            background: rgba(255,255,255,.6);
        }
        .dayTop { display: flex; align-items: center; justify-content: space-between; gap: 6px; min-width: 0; }
        .dayDate { font-weight: 900; font-size: 14px; color: #0f172a; min-width: 0; }
        .todayTag {
            font-weight: 900;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fef9c3;
            color: #854d0e;
            flex: none;
        }

        .dayIconBox { min-height: 58px; display: flex; align-items: center; justify-content: center; }
        .dayIconWrap { border-radius: 18px; padding: 8px; background: rgba(79,70,229,.12); }
        .dayIcon { width: 52px; height: 52px; object-fit: contain; display: block; }

        .dayBottom { font-weight: 900; font-size: 12px; color: #334155; letter-spacing: .02em; }

        .selected {
            background: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #fff !important;
            box-shadow: 0 10px 26px rgba(79,70,229,.28) !important;
        }
        .selected .dayDate { color: #fff !important; }
        .selected .dayBottom { color: rgba(255,255,255,.95) !important; }
        .selected .dayIconWrap { background: rgba(255,255,255,.18) !important; }
        .selected .todayTag { background: rgba(255,255,255,.18) !important; color: #fff !important; }

        /* 横スクロールは禁止 */
        .calScrollWrap { overflow-x: clip; padding-bottom: 0; }
        .calScrollInner { width: 100%; min-width: 0; }

        /* =========================
           全端末：プルダウン + 更新（統一）
        ========================= */
        .bulkPanel {
            margin-top: 10px;
            border-radius: 16px;
            border: 1px solid rgba(15,23,42,.08);
            background: rgba(255,255,255,.92);
            padding: 10px;
            box-shadow: 0 2px 10px rgba(15,23,42,.06);
        }
        .bulkPanelTitle {
            font-weight: 900;
            font-size: 12px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .bulkPanelDesc {
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            margin-top: 4px;
        }
        .bulkFormRow {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
            align-items: end;
        }
        .bulkSelectLabel {
            display: block;
            font-size: 10px;
            color: #475569;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .bulkSelect {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(148,163,184,.45);
            padding: 10px 10px;
            background: #fff;
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            outline: none;
        }
        .bulkApplyBtn {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 11px 12px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            background: #0ea5e9;
            color: #fff;
            box-shadow: 0 2px 10px rgba(14,165,233,.22);
        }
        .bulkApplyBtn.off {
            background: #ef4444;
            box-shadow: 0 2px 10px rgba(239,68,68,.20);
        }
        .bulkApplyBtn:disabled {
            opacity: .45;
            cursor: not-allowed;
            box-shadow: none;
        }

        @media (max-width: 640px) {
            .calWrap { padding: 10px; border-radius: 18px; }
            .calBadge { width: 34px; height: 34px; border-radius: 14px; font-size: 16px; }
            .calRange { font-size: 11px; }
            .calBtn { padding: 8px 10px; font-size: 12px; }

            .calScrollWrap { overflow-x: hidden !important; }
            .calScrollInner { width: 100% !important; min-width: 0 !important; }

            .dowRow,
            .calGrid { grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; width: 100%; max-width: 100%; }
            .dowRow { margin-top: 10px; font-size: 10px; }

            .dayCell {
                padding: 4px;
                border-radius: 10px;
                aspect-ratio: 1 / 1;
                min-height: 52px;
            }
            .dayDate { font-size: 10px; line-height: 1.0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .dayDate .dowText { display: none; }
            .todayTag { font-size: 8px; padding: 1px 5px; }
            .dayIconBox { min-height: 26px; }
            .dayIconWrap { padding: 3px; border-radius: 10px; }
            .dayIcon { width: 22px; height: 22px; }
            .dayBottom { font-size: 9px; line-height: 1.0; }
            .dayCell:hover { transform: none; box-shadow: 0 4px 10px rgba(15,23,42,.10); }

            /* スマホは縦積み */
            .bulkFormRow { grid-template-columns: 1fr 1fr; }
            .bulkFormRow > div:last-child { grid-column: 1 / -1; }
            .bulkApplyBtn { grid-column: 1 / -1; }
            #bulkApplyWrap { grid-column: 1 / -1; }
        }
    </style>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-2xl p-6">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-800">参加できる日をえらぼう</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $child->full_name }}（ID {{ $child->child_code }}）
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            日付を押すと「参加する／しない」が切り替わります（過去は選べません）。
                        </p>
                    </div>

                    <a href="{{ route('family.home') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        戻る
                    </a>
                </div>

                <div class="mt-6 calWrap">
                    <div class="calHead">
                        <div class="calTitle">
                            <div class="calBadge">📅</div>
                            <div>
                                <div style="font-weight: 900; font-size: 15px; color:#0f172a;">4週間カレンダー</div>
                                <div class="calRange">{{ $rangeLabel }}</div>
                            </div>
                        </div>

                        <div class="calBtns">
                            <a class="calBtn" href="{{ route('family.availability.index', ['start' => $prevStart]) }}">← 前の4週間</a>
                            <a class="calBtn" href="{{ route('family.availability.index') }}">今週から</a>
                            <a class="calBtn" href="{{ route('family.availability.index', ['start' => $nextStart]) }}">次の4週間 →</a>
                        </div>
                    </div>

                    {{-- =========================
                       全端末：プルダウン（ON/OFF）に統一
                    ========================= --}}
                    <div class="bulkPanel">
                        <div class="bulkPanelTitle">🧩 一括操作</div>
                        <div class="bulkPanelDesc">
                            未来（今日以降）のみ対象。過去は変更しません。
                        </div>

                        <div class="bulkFormRow">
                            <div>
                                <label class="bulkSelectLabel" for="bulkAction">操作</label>
                                <select id="bulkAction" class="bulkSelect">
                                    <option value="on">一括ON</option>
                                    <option value="off">一括OFF</option>
                                </select>
                            </div>

                            <div>
                                <label class="bulkSelectLabel" for="bulkType">対象</label>
                                <select id="bulkType" class="bulkSelect">
                                    <option value="grid_weekdays">この4週間：平日（月〜金）</option>
                                    <option value="month_weekday">{{ $monthLabel }}：曜日を選んで全て</option>
                                </select>
                            </div>

                            <div>
                                <label class="bulkSelectLabel" for="bulkWeekday">曜日</label>
                                <select id="bulkWeekday" class="bulkSelect">
                                    <option value="1">月曜</option>
                                    <option value="2">火曜</option>
                                    <option value="3">水曜</option>
                                    <option value="4">木曜</option>
                                    <option value="5">金曜</option>
                                    <option value="6">土曜</option>
                                    <option value="0">日曜</option>
                                </select>
                            </div>

                            <div id="bulkApplyWrap">
                                <label class="bulkSelectLabel" style="opacity:.0;">更新</label>
                                <button type="button" class="bulkApplyBtn" id="bulkApplyBtn">更新</button>
                            </div>
                        </div>
                    </div>

                    {{-- カレンダー --}}
                    <div class="calScrollWrap">
                        <div class="calScrollInner">
                            <div class="dowRow">
                                @foreach($weekdays as $i => $w)
                                    <div class="{{ $i === 0 ? 'dowSun' : ($i === 6 ? 'dowSat' : '') }}">{{ $w }}</div>
                                @endforeach
                            </div>

                            <div class="calGrid" id="calendarGrid"
                                 data-grid-start="{{ $gridStart->toDateString() }}"
                                 data-grid-end="{{ $gridEnd->toDateString() }}"
                                 data-month-start="{{ $monthStart }}"
                                 data-month-end="{{ $monthEnd }}">
                                @for ($i = 0; $i < 28; $i++)
                                    @php
                                        $dateObj = $gridStart->copy()->addDays($i);
                                        $date = $dateObj->toDateString();
                                        $dow = (int)$dateObj->dayOfWeek; // 0=日
                                        $dowLabel = $weekdays[$dow] ?? '';
                                        $isSelected = $selectedDates->has($date);
                                        $isPast = $dateObj->lt(now()->startOfDay());
                                        $isToday = ($date === $today);
                                    @endphp

                                    <button type="button"
                                            class="dayCell {{ $isSelected ? 'selected' : '' }}"
                                            data-date="{{ $date }}"
                                            data-weekday="{{ $dow }}"
                                            {{ $isPast ? 'disabled' : '' }}>
                                        <div class="dayTop">
                                            <div class="dayDate">
                                                {{ $dateObj->format('n/j') }}
                                                <span class="dowText">（{{ $dowLabel }}）</span>
                                            </div>
                                            @if($isToday)
                                                <div class="todayTag">今日</div>
                                            @endif
                                        </div>

                                        <div class="dayIconBox" data-role="icon">
                                            @if($isSelected)
                                                <div class="dayIconWrap">
                                                    <img src="{{ asset('images/attendance.png') }}" alt="参加予定" class="dayIcon">
                                                </div>
                                            @else
                                                <span style="width:52px;height:52px;display:block;"></span>
                                            @endif
                                        </div>

                                        <div class="dayBottom" data-role="bottom">
                                            {!! $isSelected ? '参加' : '&nbsp;' !!}
                                        </div>
                                    </button>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>

                <div id="toast"
                     class="fixed top-4 right-4 hidden rounded-2xl bg-gray-900 text-white px-4 py-3 text-sm shadow-lg">
                    保存しました！
                </div>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const grid = document.getElementById('calendarGrid');
            const toast = document.getElementById('toast');

            const toggleUrl = @json(route('family.availability.toggle'));
            const bulkUrl   = @json(route('family.availability.bulk_on')); // NOTE: ここはサーバ側でON/OFF対応にする
            const csrf      = @json(csrf_token());

            function showToast(msg) {
                toast.textContent = msg;
                toast.classList.remove('hidden');
                setTimeout(() => toast.classList.add('hidden'), 1400);
            }

            function setSelectedUI(btn, on) {
                if (on) btn.classList.add('selected');
                else btn.classList.remove('selected');

                const iconBox = btn.querySelector('[data-role="icon"]');
                const bottom = btn.querySelector('[data-role="bottom"]');

                if (iconBox) {
                    if (on) {
                        iconBox.innerHTML = `
                            <div class="dayIconWrap">
                                <img src="{{ asset('images/attendance.png') }}" alt="参加予定" class="dayIcon">
                            </div>
                        `;
                    } else {
                        iconBox.innerHTML = `<span style="width:52px;height:52px;display:block;"></span>`;
                    }
                }
                if (bottom) bottom.innerHTML = on ? '参加' : '&nbsp;';
            }

            async function toggleDate(date, btn) {
                try {
                    const res = await fetch(toggleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ date }),
                    });

                    const data = await res.json();
                    if (!res.ok || !data.ok) {
                        showToast(data.message || '保存に失敗しました');
                        return;
                    }

                    setSelectedUI(btn, data.status === 'on');
                    showToast(data.status === 'on' ? '参加にしました！' : '参加を解除しました');
                } catch (e) {
                    showToast('通信エラーです');
                }
            }

            function ymddToDate(s) {
                const [y,m,d] = String(s).split('-').map(v => parseInt(v,10));
                return new Date(y, (m||1)-1, d||1);
            }

            function applyBulkUI(weekdays, start, end, action) {
                const wSet = new Set((weekdays || []).map(String));
                const startD = ymddToDate(start);
                const endD   = ymddToDate(end);
                endD.setHours(0,0,0,0);
                startD.setHours(0,0,0,0);

                const btns = grid.querySelectorAll('button[data-date][data-weekday]');
                btns.forEach(btn => {
                    if (btn.disabled) return;

                    const dt = btn.dataset.date;
                    const wd = String(btn.dataset.weekday);

                    const dObj = ymddToDate(dt);
                    if (dObj < startD || dObj > endD) return;
                    if (!wSet.has(wd)) return;

                    setSelectedUI(btn, action === 'on');
                });
            }

            async function bulkApply(payload) {
                const res = await fetch(bulkUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) throw new Error(data?.message || '一括保存に失敗しました');
                return data;
            }

            if (!grid) return;

            // 個別トグル
            grid.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-date]');
                if (!btn || btn.disabled) return;
                toggleDate(btn.dataset.date, btn);
            });

            // ========= 全端末: プルダウン ON/OFF
            const bulkAction = document.getElementById('bulkAction');
            const bulkType = document.getElementById('bulkType');
            const bulkWeekday = document.getElementById('bulkWeekday');
            const bulkApplyBtn = document.getElementById('bulkApplyBtn');

            const weekdays = [1,2,3,4,5];

            function syncBulkUiState() {
                if (!bulkType || !bulkWeekday) return;
                const isMonth = bulkType.value === 'month_weekday';
                bulkWeekday.disabled = !isMonth;
            }

            function syncApplyButtonStyle() {
                if (!bulkAction || !bulkApplyBtn) return;
                const isOff = bulkAction.value === 'off';
                bulkApplyBtn.classList.toggle('off', isOff);
            }

            if (bulkType) {
                bulkType.addEventListener('change', syncBulkUiState);
                syncBulkUiState();
            }
            if (bulkAction) {
                bulkAction.addEventListener('change', syncApplyButtonStyle);
                syncApplyButtonStyle();
            }

            if (bulkApplyBtn) {
                bulkApplyBtn.addEventListener('click', async () => {
                    const action = bulkAction ? bulkAction.value : 'on';
                    const typeVal = bulkType ? bulkType.value : 'grid_weekdays';
                    const gridStart = grid.dataset.gridStart;
                    const gridEnd   = grid.dataset.gridEnd;

                    try {
                        showToast('まとめて保存中...');

                        if (typeVal === 'grid_weekdays') {
                            const data = await bulkApply({
                                mode: 'grid',
                                start: gridStart,
                                end: gridEnd,
                                weekdays,
                                action
                            });
                            applyBulkUI(weekdays, gridStart, gridEnd, action);
                            showToast(`平日を一括${action === 'on' ? 'ON' : 'OFF'}しました（${data.count}件）`);
                            return;
                        }

                        const weekday = parseInt(bulkWeekday.value, 10);
                        const monthStart = grid.dataset.monthStart;
                        const monthEnd   = grid.dataset.monthEnd;

                        const data = await bulkApply({
                            mode: 'month',
                            start: monthStart,
                            end: monthEnd,
                            weekdays: [weekday],
                            action
                        });

                        applyBulkUI([weekday], gridStart, gridEnd, action);
                        showToast(`曜日を一括${action === 'on' ? 'ON' : 'OFF'}しました（${data.count}件）`);
                    } catch (e) {
                        showToast(e.message || 'エラー');
                    }
                });
            }
        })();
    </script>
</x-app-layout>
