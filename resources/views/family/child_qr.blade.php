<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                @if(($siblings ?? collect())->count() > 1)
                    <div class="mb-4 overflow-x-auto">
                        <div class="inline-flex items-center gap-2 min-w-max">
                            @foreach($siblings as $s)
                                <a href="{{ route('family.child.qr', ['child_id' => $s->id]) }}"
                                   class="px-3 py-1.5 rounded-full text-xs font-semibold border
                                          {{ (int)$s->id === (int)$child->id
                                              ? 'bg-emerald-100 border-emerald-300 text-emerald-800'
                                              : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                    {{ $s->full_name }}（{{ $s->grade }}年）
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-start gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-800">児童QR表示</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $child->full_name ?? '—' }}（ID {{ $child->child_code ?? $child->id }}）
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ $child->school?->name ?? '—' }} / {{ $child->grade ?? '—' }}年 / {{ $child->base?->name ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            受付でこのQRを提示してください。
                        </p>
                        <div class="mt-3">
                            <a href="{{ route('family.availability.index', ['child_id' => (int)$child->id]) }}"
                               class="inline-flex items-center rounded-full bg-indigo-50 border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                この児童の送迎登録へ
                            </a>
                        </div>
                    </div>

                    <div class="ms-auto">
                        <div id="attendance-status"
                             class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[12px] font-semibold text-emerald-800 shadow-sm whitespace-nowrap sm:px-4 sm:py-2 sm:text-sm">
                            <span id="attendance-dot" class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span id="attendance-label">参加中</span>
                            <span id="attendance-time" class="font-mono text-emerald-700"></span>
                        </div>
                    </div>
                </div>

                @if (session('error'))
                    <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mt-6 flex flex-col items-center">
                    <div class="w-full max-w-sm rounded-xl border border-gray-200 bg-gray-50 p-5 flex flex-col items-center">
                        <div id="qrcode" class="bg-white p-4 rounded-lg shadow-sm"></div>

                        <div class="mt-4 text-xs text-gray-500 text-center">
                            画面の明るさを上げると読み取りやすくなります。
                        </div>

                        <button
                            type="button"
                            id="openQrModal"
                            class="mt-4 w-full max-w-sm group"
                        >
                            <div
                                class="flex items-center justify-center gap-3 rounded-2xl px-4 py-3
                                       bg-slate-50 border border-slate-200 shadow-sm
                                       transition-all duration-200
                                       group-hover:bg-slate-100 group-hover:-translate-y-0.5 group-hover:shadow
                                       focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                            >
                                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border border-slate-200">
                                    <img src="{{ asset('images/expansion.png') }}" alt="拡大" class="w-7 h-7 object-contain">
                                </span>

                                <div class="min-w-0 text-left leading-tight">
                                    <div class="text-sm font-semibold text-gray-900">
                                        QRコード
                                    </div>
                                    <div class="text-xs font-semibold text-gray-700">
                                        （おおきくする）
                                    </div>
                                </div>

                                <img src="{{ asset('images/press.png') }}" alt="押す" class="w-10 h-10 object-contain">
                            </div>
                        </button>

                        <div class="mt-3 text-[11px] text-gray-400 text-center">
                            ※印刷が難しい場合は、この画面をそのまま提示でもOKです
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- モーダル --}}
    <div id="qrModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" id="qrModalBackdrop"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-base font-semibold text-gray-800">QRコード（拡大）</div>
                    <button type="button" id="closeQrModal"
                            class="px-3 py-1 rounded bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">
                        閉じる
                    </button>
                </div>

                <div class="flex items-center justify-center">
                    <div class="rounded-xl border bg-white p-4">
                        <img id="qrModalImg" alt="QR" class="w-80 h-80 object-contain">
                    </div>
                </div>

                <div class="mt-4 text-center text-xs text-gray-500">
                    {{ $child->full_name ?? '—' }} / {{ $child->school?->name ?? '—' }} / ID: {{ $child->child_code ?? $child->id }}
                </div>
            </div>
        </div>
    </div>

    {{-- QR生成ライブラリ --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        (function () {
            const payload = @json($payload);
            const el = document.getElementById('qrcode');
            if (!el) return;

            // 既存クリア
            el.innerHTML = '';

            const qr = new QRCode(el, {
                text: payload,
                width: 240,
                height: 240,
                correctLevel: QRCode.CorrectLevel.M
            });

            function getQrDataUrl() {
                const img = el.querySelector('img');
                if (img && img.src) return img.src;

                const canvas = el.querySelector('canvas');
                if (canvas) return canvas.toDataURL('image/png');

                return '';
            }

            const modal = document.getElementById('qrModal');
            const modalImg = document.getElementById('qrModalImg');

            function openModal() {
                modalImg.src = getQrDataUrl();
                modal.classList.remove('hidden');
            }
            function closeModal() {
                modal.classList.add('hidden');
            }

            document.getElementById('openQrModal').addEventListener('click', openModal);
            document.getElementById('closeQrModal').addEventListener('click', closeModal);
            document.getElementById('qrModalBackdrop').addEventListener('click', closeModal);

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>

    <script>
        (function () {
            const statusUrl = @json($statusUrl ?? '');
            if (!statusUrl) return;

            const statusEl = document.getElementById('attendance-status');
            const timeEl = document.getElementById('attendance-time');
            const labelEl = document.getElementById('attendance-label');
            const dotEl = document.getElementById('attendance-dot');

            function updateUI(data) {
                if (!statusEl || !timeEl || !labelEl || !dotEl) return;
                const state = String(data?.state || '');

                if (state === 'pickup') {
                    labelEl.textContent = '送迎中';
                    timeEl.textContent = data?.pickup_time ? ` ${data.pickup_time}` : '';
                    statusEl.classList.remove('hidden');
                    statusEl.classList.add('flex');
                    statusEl.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                    statusEl.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
                    timeEl.classList.remove('text-emerald-700');
                    timeEl.classList.add('text-amber-700');
                    dotEl.classList.remove('bg-emerald-500');
                    dotEl.classList.add('bg-amber-500');
                } else if (state === 'attending') {
                    labelEl.textContent = '参加中';
                    timeEl.textContent = data?.time ? ` ${data.time}` : '';
                    statusEl.classList.remove('hidden');
                    statusEl.classList.add('flex');
                    statusEl.classList.remove('border-amber-200', 'bg-amber-50', 'text-amber-800');
                    statusEl.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
                    timeEl.classList.remove('text-amber-700');
                    timeEl.classList.add('text-emerald-700');
                    dotEl.classList.remove('bg-amber-500');
                    dotEl.classList.add('bg-emerald-500');
                } else {
                    statusEl.classList.add('hidden');
                    statusEl.classList.remove('flex');
                }
            }

            async function fetchStatus() {
                try {
                    const res = await fetch(statusUrl, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data && data.ok) {
                        updateUI(data);
                    }
                } catch (e) {
                    // no-op
                }
            }

            fetchStatus();
            setInterval(fetchStatus, 15000);

            // 日付が変わったら次回ポーリングで更新されるが、念のため1分ごとに再確認
            setInterval(fetchStatus, 60000);
        })();
    </script>

</x-app-layout>
