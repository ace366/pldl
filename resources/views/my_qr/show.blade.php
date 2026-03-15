<x-app-layout>
    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h1 class="text-lg font-semibold text-gray-800 mb-4">スタッフQRコード（出勤用）</h1>

                {{-- QR --}}
                <div class="flex flex-col items-center">
                    <div class="rounded-xl border bg-white p-4 shadow-sm">
                        <div id="qrBox" class="w-56 h-56 flex items-center justify-center"></div>
                    </div>

                    {{-- ✅ 拡大ボタン：横並び＋2行（バランス良く） --}}
                    <button
                        type="button"
                        id="openQrModal"
                        class="mt-4 w-full max-w-xs sm:max-w-sm group"
                    >
                        <div
                            class="flex items-center justify-center gap-3 rounded-2xl px-4 py-3
                                   bg-slate-50 border border-slate-200 shadow-sm
                                   transition-all duration-200
                                   group-hover:bg-slate-100 group-hover:-translate-y-0.5 group-hover:shadow
                                   focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                        >
                            {{-- 左：拡大アイコン --}}
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border border-slate-200">
                                <img src="{{ asset('images/expansion.png') }}" alt="拡大" class="w-7 h-7 object-contain">
                            </span>

                            {{-- 中：2行テキスト --}}
                            <div class="min-w-0 text-left leading-tight">
                                <div class="text-sm font-semibold text-gray-900">
                                    QRコード
                                </div>
                                <div class="text-xs font-semibold text-gray-700">
                                    （おおきくする）
                                </div>
                            </div>

                            {{-- 右：pressアイコン --}}
                            <img src="{{ asset('images/press.png') }}" alt="押す" class="w-10 h-10 object-contain">
                        </div>
                    </button>

                    {{-- 情報表示 --}}
                    <div class="mt-5 w-full rounded-lg bg-gray-50 border p-4">
                        <div class="text-sm text-gray-700">
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-500">名前</span>
                                <span class="font-semibold text-gray-900">{{ $name }}</span>
                            </div>

                            {{-- ✅ 学校ではなく拠点 --}}
                            <div class="flex justify-between gap-3 mt-2">
                                <span class="text-gray-500">{{ $orgLabel ?? '拠点' }}</span>
                                <span class="font-semibold text-gray-900">{{ $orgValue ?? '—' }}</span>
                            </div>

                            <div class="mt-3 text-xs text-gray-500">
                                スタッフID：<span class="font-mono">{{ $loginId }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-xs text-gray-500 text-center">
                        ※このQRは「出勤打刻」専用です（児童受付では使用しません）
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
                        {{ $name }} / {{ $orgValue ?? '—' }} / ID: {{ $loginId }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- qrcodejs（CDN） --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        // QR生成
        const payload = @js($qrPayload);

        const qrBox = document.getElementById('qrBox');
        const qr = new QRCode(qrBox, {
            text: payload,
            width: 224,
            height: 224,
            correctLevel: QRCode.CorrectLevel.M
        });

        // 生成されたQR（img or canvas）をモーダル用に取り出す
        function getQrDataUrl() {
            const img = qrBox.querySelector('img');
            if (img && img.src) return img.src;

            const canvas = qrBox.querySelector('canvas');
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

        // ESCで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    </script>
</x-app-layout>
