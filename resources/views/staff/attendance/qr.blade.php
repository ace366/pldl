{{-- resources/views/staff/attendance/qr.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-800">スタッフ QR 打刻</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $staffName ?? 'スタッフ' }} さん
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            この画面でQRを読み取ると「出勤 / 退勤」を自動判定して打刻します。
                        </p>
                    </div>

                    <a href="{{ route('staff.attendance.today') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        戻る
                    </a>
                </div>

                @if (session('error'))
                    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- スキャナ枠 --}}
                <div class="mt-6">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-sm font-semibold text-gray-700 mb-3">
                            カメラでQRを読み取ってください
                        </div>

                        <div id="qrReader"
                             class="w-full overflow-hidden rounded-xl bg-white border border-gray-200"></div>

                        <div class="mt-3 text-[11px] text-gray-500 leading-relaxed">
                            ※ iPhone/iPad で「カメラ許可」が出たら許可してください。<br>
                            ※ 読み取りづらい場合は画面の明るさを上げてください。
                        </div>

                        {{-- 状態表示 --}}
                        <div id="toast"
                             class="mt-4 hidden rounded-xl border px-4 py-3 text-sm font-semibold"></div>

                        {{-- 予備：手動送信（デバッグ用、必要なければ後で消してOK） --}}
                        <div class="mt-4 hidden">
                            <input id="manualPayload" class="w-full rounded-lg border px-3 py-2 text-sm"
                                   placeholder="STAFF_ID:123" />
                            <button id="manualSend"
                                    class="mt-2 w-full rounded-lg bg-indigo-600 px-4 py-2 text-white font-semibold hover:bg-indigo-700">
                                手動で送信
                            </button>
                        </div>
                    </div>
                </div>

                {{-- CSRF / endpoint --}}
                <form id="qrClockForm" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    {{-- html5-qrcode --}}
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        (function () {
            const endpoint = @json(route('staff.attendance.qr_clock'));

            // 連投防止
            let cooldown = false;
            const COOLDOWN_MS = 1800;

            const toast = document.getElementById('toast');

            function showToast(type, message) {
                // type: success | error | info
                toast.classList.remove('hidden');
                toast.className = "mt-4 rounded-xl border px-4 py-3 text-sm font-semibold";

                if (type === 'success') {
                    toast.classList.add('bg-emerald-50','border-emerald-200','text-emerald-800');
                } else if (type === 'error') {
                    toast.classList.add('bg-red-50','border-red-200','text-red-800');
                } else {
                    toast.classList.add('bg-slate-50','border-slate-200','text-slate-800');
                }

                toast.textContent = message;
            }

            async function postPayload(payload) {
                const token = document.querySelector('#qrClockForm input[name="_token"]').value;

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ payload: payload })
                });

                let data = null;
                try { data = await res.json(); } catch (e) {}

                if (!res.ok) {
                    const msg = (data && data.message) ? data.message : '打刻に失敗しました。';
                    throw new Error(msg);
                }

                return data;
            }

            function formatResult(data) {
                // controller: { ok, action, message }
                const action = (data && data.action) ? data.action : '';
                const msg = (data && data.message) ? data.message : '完了しました。';

                if (action === 'clock_in') return '✅ 出勤：' + msg;
                if (action === 'clock_out') return '🏁 退勤：' + msg;
                if (action === 'none') return 'ℹ️ ' + msg;
                return msg;
            }

            async function handleScan(decodedText) {
                if (cooldown) return;
                cooldown = true;

                try {
                    showToast('info', '送信中…');

                    const data = await postPayload(decodedText);

                    if (data && data.ok) {
                        showToast('success', formatResult(data));
                    } else {
                        // ok=falseでもサーバは409などで返している想定だが保険
                        showToast('error', formatResult(data));
                    }
                } catch (err) {
                    showToast('error', err.message || '打刻に失敗しました。');
                } finally {
                    setTimeout(() => { cooldown = false; }, COOLDOWN_MS);
                }
            }

            // 初期化
            const elId = "qrReader";
            const reader = new Html5Qrcode(elId);

            const config = {
                fps: 10,
                qrbox: { width: 240, height: 240 },
                aspectRatio: 1.0,
                disableFlip: true,
            };

            async function start() {
                try {
                    // 背面カメラ優先（対応ブラウザでは効く）
                    await reader.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => handleScan(decodedText),
                        () => {}
                    );
                } catch (e) {
                    // フォールバック（デバイス一覧から開始）
                    try {
                        const devices = await Html5Qrcode.getCameras();
                        if (!devices || devices.length === 0) {
                            showToast('error', 'カメラが見つかりませんでした。');
                            return;
                        }
                        await reader.start(
                            devices[0].id,
                            config,
                            (decodedText) => handleScan(decodedText),
                            () => {}
                        );
                    } catch (e2) {
                        showToast('error', 'カメラを起動できませんでした（権限やHTTPSを確認してください）。');
                    }
                }
            }

            start();

            // 手動送信（隠し機能）
            const manualSend = document.getElementById('manualSend');
            const manualPayload = document.getElementById('manualPayload');
            if (manualSend && manualPayload) {
                manualSend.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const v = (manualPayload.value || '').trim();
                    if (!v) return showToast('error', '値を入力してください。');
                    await handleScan(v);
                });
            }

            // ページ離脱時停止
            window.addEventListener('beforeunload', () => {
                try { reader.stop(); } catch (e) {}
            });
        })();
    </script>
</x-app-layout>
