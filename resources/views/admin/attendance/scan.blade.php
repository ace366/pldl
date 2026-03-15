<x-app-layout>
    <div class="bg-slate-50">
        {{-- ✅ スマホは縦余白を詰める / 下固定タブがある場合に被らない余白を確保 --}}
        <div class="py-3 sm:py-6 pb-24 sm:pb-6">
            <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-2xl p-4 sm:p-6">

                    {{-- ヘッダー（スマホは詰める） --}}
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h1 class="text-base sm:text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <img src="{{ asset('images/QR.gif') }}" alt="QR" class="w-6 h-6 sm:w-7 sm:h-7 object-contain">
                                出席登録（QR読み取り）
                            </h1>
                            <p class="text-xs sm:text-sm text-gray-600 mt-1">
                                カメラでQRを読み取ると、児童を特定して出席を記録します。
                            </p>
                        </div>

                        <button type="button" id="enableTtsBtn"
                                class="shrink-0 px-3 py-2 rounded-xl bg-slate-600 text-white text-xs font-semibold hover:bg-slate-700 active:scale-[0.99]">
                            音声案内OFF
                        </button>
                    </div>

                    {{-- ✅ スキャナ＋結果（スマホは1カラム、PCは2カラム） --}}
                    <div class="mt-3 sm:mt-5 grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-5">

                        {{-- 左：スキャナ --}}
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xs sm:text-sm font-semibold text-slate-700">カメラ</div>
                                <div class="text-[10px] sm:text-xs text-slate-500">明るい場所推奨</div>
                            </div>

                            {{-- ✅ カメラ枠（スマホは少し小さく・無駄な余白なし） --}}
                            <div class="mt-2 sm:mt-3 flex justify-center">
                                <div class="w-[280px] sm:w-[360px] md:w-[420px]">
                                    <div id="qr-reader" class="w-full"></div>
                                </div>
                            </div>

                            <div class="mt-2 text-[10px] sm:text-xs text-slate-500 text-center leading-relaxed">
                                読み取りが不安定な場合は、少し離す／角度を変える／画面拡大を試してください。
                            </div>
                        </div>

                        {{-- 右：結果＋手入力 --}}
                        <div class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                            <div class="text-xs sm:text-sm font-semibold text-slate-700 mb-2">読み取り結果</div>

                            <div id="resultBox" class="rounded-xl bg-slate-50 border border-slate-200 p-3 sm:p-4">
                                <div class="text-sm text-slate-700">未読み取り</div>
                            </div>

                            <div class="mt-2 sm:mt-3">
                                <div class="text-[10px] sm:text-xs text-slate-500 mb-1">最終メッセージ</div>
                                <div id="messageBox" class="text-sm text-slate-900">—</div>
                            </div>

                            <div class="mt-4 sm:mt-5">
                                <label class="block text-[10px] sm:text-xs text-slate-600 mb-1">QRカード忘れ用（手入力QR）</label>

                                <div class="flex items-start gap-3">
                                    <input id="manualQr" type="text"
                                           class="flex-1 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                           placeholder="例：CHILD:1234 / 1234 / LOGIN:xxxx / 1">

                                    <button type="button" id="manualSend"
                                            class="group inline-flex flex-col items-center gap-1 shrink-0">
                                        <span class="inline-flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-full
                                                    bg-white border border-slate-200 shadow-sm
                                                    transition-all duration-200
                                                    group-hover:bg-slate-50 group-hover:-translate-y-0.5 group-hover:shadow
                                                    active:scale-[0.98]
                                                    focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                            <img src="{{ asset('images/send.png') }}" alt="送信" class="w-6 h-6 sm:w-7 sm:h-7 object-contain">
                                        </span>
                                        <span class="text-xs sm:text-sm font-semibold text-slate-900">送信</span>
                                    </button>
                                </div>
                            </div>

                            {{-- ✅ 追加：ショートカット --}}
                            <div class="mt-3 text-[10px] sm:text-xs text-slate-500">
                                Enterで送信できます（手入力欄フォーカス時）。
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ✅ 成功オーバーレイ（現場で見やすい） --}}
    <div id="successOverlay" class="hidden fixed inset-0 z-[60]">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-emerald-50 via-white to-indigo-50">
                    <div class="text-xs font-semibold text-slate-600">出席を登録しました</div>
                    <div id="overlayName" class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        —
                    </div>
                    <div id="overlaySub" class="mt-2 text-sm text-slate-600">
                        —
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="text-sm text-slate-700">
                        次の児童を読み取れます。
                    </div>

                    <button type="button" id="overlayClose"
                            class="mt-4 w-full inline-flex justify-center items-center rounded-2xl bg-emerald-600 px-4 py-3
                                   text-sm font-extrabold text-white hover:bg-emerald-700 active:scale-[0.99]">
                        OK（つづける）
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- なぞなぞポップアップ --}}
    <div id="riddleOverlay" class="hidden fixed inset-0 z-[70]">
        <div class="absolute inset-0 bg-black/45 backdrop-blur-sm"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl border border-indigo-200 overflow-hidden">
                <div class="px-5 py-4 bg-gradient-to-r from-indigo-50 via-white to-sky-50">
                    <div class="text-xs font-semibold text-slate-600">なぞなぞ</div>
                    <div class="mt-2 text-base sm:text-lg font-extrabold text-slate-900 leading-relaxed">
                        <span id="riddleQuestion">—</span>
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div id="riddleAnswerWrap" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="text-xs font-semibold text-emerald-700">こたえ</div>
                        <div id="riddleAnswer" class="mt-1 text-sm sm:text-base font-bold text-emerald-900">—</div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" id="riddleShowAnswer"
                                class="inline-flex justify-center items-center rounded-2xl bg-indigo-600 px-4 py-3
                                       text-sm font-extrabold text-white hover:bg-indigo-700 active:scale-[0.99]">
                            こたえを見る
                        </button>
                        <button type="button" id="riddleClose"
                                class="inline-flex justify-center items-center rounded-2xl bg-slate-200 px-4 py-3
                                       text-sm font-bold text-slate-800 hover:bg-slate-300 active:scale-[0.99]">
                            とじる
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- html5-qrcode --}}
    <script src="https://unpkg.com/html5-qrcode"></script>

    {{-- ✅ html5-qrcode の中身が暴れないようにする --}}
    <style>
        #qr-reader video,
        #qr-reader canvas {
            width: 100% !important;
            height: auto !important;
            border-radius: 16px;
        }
        #qr-reader__dashboard_section { margin-top: 6px; }
        /* スマホでは html5-qrcode のボタン群がデカいので少しだけ詰める */
        #qr-reader__dashboard_section button,
        #qr-reader__dashboard_section select {
            font-size: 12px;
        }
    </style>

    <script>
        const logUrl = @js(route('admin.attendance.log'));
        const csrf   = @js(csrf_token());

        // 連続発火防止
        let cooldown = false;
        const COOLDOWN_MS = 1800;

        // 同じQRの連打防止
        let lastQr = null;
        let lastQrAt = 0;
        const SAME_QR_BLOCK_MS = 2500;

        // TTS
        let ttsEnabled = false;
        let ttsReady = false;

        const resultBox = document.getElementById('resultBox');

        // overlay
        const overlay = document.getElementById('successOverlay');
        const overlayName = document.getElementById('overlayName');
        const overlaySub  = document.getElementById('overlaySub');
        const overlayClose = document.getElementById('overlayClose');
        let overlayAutoCloseTimer = null;

        const riddleOverlay = document.getElementById('riddleOverlay');
        const riddleQuestion = document.getElementById('riddleQuestion');
        const riddleAnswerWrap = document.getElementById('riddleAnswerWrap');
        const riddleAnswer = document.getElementById('riddleAnswer');
        const riddleShowAnswer = document.getElementById('riddleShowAnswer');
        const riddleClose = document.getElementById('riddleClose');
        let currentRiddle = null;

        function openOverlay(child) {
            const name = child?.name ?? '—';
            const school = child?.school ?? '—';
            const grade  = child?.grade ?? '—';
            const code   = child?.child_code ? `ID ${child.child_code}` : '';

            overlayName.textContent = name;
            overlaySub.textContent = `${school} / ${grade}年 ${code ? ' / ' + code : ''}`.trim();
            overlay.classList.remove('hidden');

            if (overlayAutoCloseTimer) {
                clearTimeout(overlayAutoCloseTimer);
            }
            overlayAutoCloseTimer = setTimeout(() => {
                closeOverlay();
            }, 3000);
        }

        function closeOverlay() {
            overlay.classList.add('hidden');
            if (overlayAutoCloseTimer) {
                clearTimeout(overlayAutoCloseTimer);
                overlayAutoCloseTimer = null;
            }
        }

        function openRiddleOverlay(riddle) {
            currentRiddle = riddle ?? null;
            riddleQuestion.textContent = riddle?.question ?? '—';
            riddleAnswer.textContent = riddle?.answer ?? '—';
            riddleAnswerWrap.classList.add('hidden');
            riddleShowAnswer.disabled = !(riddle?.answer);
            riddleShowAnswer.textContent = 'こたえを見る';
            riddleShowAnswer.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
            riddleShowAnswer.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
            riddleOverlay.classList.remove('hidden');
        }

        function closeRiddleOverlay() {
            riddleOverlay.classList.add('hidden');
            currentRiddle = null;
        }

        overlayClose.addEventListener('click', closeOverlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.classList.contains('backdrop-blur-sm')) closeOverlay();
        });
        riddleClose.addEventListener('click', closeRiddleOverlay);
        riddleOverlay.addEventListener('click', (e) => {
            if (e.target === riddleOverlay || e.target.classList.contains('backdrop-blur-sm')) closeRiddleOverlay();
        });

        document.getElementById('enableTtsBtn').addEventListener('click', async () => {
            ttsEnabled = !ttsEnabled;

            if (ttsEnabled) {
                await warmUpVoices();
                try {
                    const u = new SpeechSynthesisUtterance('音声案内を有効にしました。');
                    u.lang = 'ja-JP';
                    u.volume = 0.2;
                    const v = pickJapaneseFemaleVoice();
                    if (v) u.voice = v;
                    speechSynthesis.speak(u);
                } catch (e) {}
            }

            const btn = document.getElementById('enableTtsBtn');
            btn.textContent = ttsEnabled ? '音声案内ON' : '音声案内OFF';
            btn.className = ttsEnabled
                ? 'shrink-0 px-3 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 active:scale-[0.99]'
                : 'shrink-0 px-3 py-2 rounded-xl bg-slate-600 text-white text-xs font-semibold hover:bg-slate-700 active:scale-[0.99]';
        });

        async function postLog(qrText) {
            const res = await fetch(logUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ qr: qrText }),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data?.message ?? '登録に失敗しました。');
            return data;
        }

        function setResult(child) {
            const name = child?.name ?? '—';
            const school = child?.school ?? '—';
            const grade = child?.grade ?? '—';
            const code = child?.child_code ? `（ID ${child.child_code}）` : '';

            resultBox.innerHTML = `
                <div class="text-lg font-extrabold text-slate-900">${escapeHtml(name)} ${escapeHtml(code)}</div>
                <div class="text-sm text-slate-600 mt-1">${escapeHtml(school)} / ${escapeHtml(grade)}年</div>
            `;
        }

        function setMessage(msg, isError=false) {
            const box = document.getElementById('messageBox');
            box.textContent = msg ?? '—';
            box.className = isError ? 'text-sm text-rose-700 font-semibold' : 'text-sm text-slate-900';
        }

        function flashSuccess() {
            resultBox.classList.add('ring-2','ring-emerald-300');
            setTimeout(() => resultBox.classList.remove('ring-2','ring-emerald-300'), 700);
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function sleep(ms){ return new Promise(r => setTimeout(r, ms)); }

        async function warmUpVoices() {
            if (!('speechSynthesis' in window)) return;
            for (let i = 0; i < 12; i++) {
                const v = speechSynthesis.getVoices();
                if (v && v.length > 0) { ttsReady = true; return; }
                await sleep(120);
            }
            speechSynthesis.onvoiceschanged = () => { ttsReady = true; };
        }

        function pickJapaneseFemaleVoice() {
            const voices = speechSynthesis.getVoices() || [];
            if (!voices.length) return null;

            const ja = voices.filter(v => (v.lang || '').toLowerCase().startsWith('ja'));
            const pool = ja.length ? ja : voices;

            const prefer = pool.find(v => /female|woman|kyoko|haruka|ayumi|nanami|mizuki|sakura|nozomi|yui/i.test(v.name));
            if (prefer) return prefer;

            if (ja.length) return ja[0];
            return voices[0] ?? null;
        }

        function speakJa(text, options = {}) {
            if (!ttsEnabled) return;
            if (!('speechSynthesis' in window)) return;

            const cancelCurrent = options.cancelCurrent !== false;
            if (cancelCurrent) {
                try { speechSynthesis.cancel(); } catch (e) {}
            }

            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'ja-JP';
            u.rate = typeof options.rate === 'number' ? options.rate : 0.9; // 少しゆっくり
            u.pitch = typeof options.pitch === 'number' ? options.pitch : 1.08;

            const v = pickJapaneseFemaleVoice();
            if (v) u.voice = v;

            speechSynthesis.speak(u);
        }

        riddleShowAnswer.addEventListener('click', async () => {
            if (!currentRiddle?.answer) return;

            riddleAnswerWrap.classList.remove('hidden');
            riddleShowAnswer.textContent = 'もう一度こたえを聞く';
            riddleShowAnswer.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
            riddleShowAnswer.classList.add('bg-emerald-600', 'hover:bg-emerald-700');

            if (!ttsEnabled) return;
            if (!ttsReady) await warmUpVoices();

            const answerText = currentRiddle?.answer_tts ?? `こたえは、${currentRiddle.answer}。`;
            speakJa(answerText, { cancelCurrent: true, rate: 0.9, pitch: 1.06 });
        });

        async function handleQr(qrText) {
            const now = Date.now();
            if (lastQr === qrText && (now - lastQrAt) < SAME_QR_BLOCK_MS) return;
            lastQr = qrText;
            lastQrAt = now;

            if (cooldown) return;
            cooldown = true;
            setTimeout(() => cooldown = false, COOLDOWN_MS);

            try {
                setMessage('登録中...');
                const data = await postLog(qrText);

                if (data?.child) {
                    setResult(data.child);
                    flashSuccess();
                    if (!data?.riddle?.question) {
                        openOverlay(data.child);
                    }
                }
                setMessage(data?.message ?? '登録が完了しました。');

                if (data?.riddle?.question) {
                    openRiddleOverlay(data.riddle);
                }

                if (data?.tts_text) {
                    if (!ttsReady) await warmUpVoices();
                    speakJa(data.tts_text, { cancelCurrent: true, rate: 0.9, pitch: 1.08 });
                }
            } catch (e) {
                setMessage(e.message || 'エラー', true);
            }
        }

        // ✅ 手入力：Enterでも送信
        const manualQr = document.getElementById('manualQr');
        manualQr.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('manualSend').click();
            }
        });

        document.getElementById('manualSend').addEventListener('click', () => {
            const v = manualQr.value.trim();
            if (!v) return;
            handleQr(v);
            manualQr.value = '';
            manualQr.focus();
        });

        // ✅ カメラ表示（スマホは少し小さめに）
        const qrReader = new Html5QrcodeScanner(
            "qr-reader",
            { fps: 10, qrbox: { width: 240, height: 240 } }, // スマホで縦が詰まるように微調整
            false
        );

        qrReader.render(
            (decodedText) => handleQr(decodedText),
            () => {}
        );
    </script>
</x-app-layout>
