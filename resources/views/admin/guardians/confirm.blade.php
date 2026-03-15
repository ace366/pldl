{{-- resources/views/guardian/confirm.blade.php --}}
<x-guest-layout>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-lg font-semibold text-gray-800 mb-2">登録内容の確認</h1>
            <p class="text-sm text-gray-600 mb-4">
                こちらは保護者の確認ページです。内容に誤りがある場合はスタッフへご連絡ください。
            </p>

            {{-- 保護者情報 --}}
            <div class="rounded-xl border p-4 bg-gray-50">
                <div class="text-sm font-semibold text-gray-800 mb-2">保護者情報</div>
                <div class="text-sm text-gray-700 space-y-1">
                    <div>お名前：<span class="font-semibold">{{ $guardian->name ?? ($guardian->last_name.' '.$guardian->first_name) }}</span></div>
                    <div>電話：{{ $guardian->phone ?? '—' }}</div>
                    <div>メール：{{ $guardian->email ?? '—' }}</div>
                    <div>希望連絡手段：{{ $guardian->preferred_contact ?? '—' }}</div>
                </div>
            </div>

            {{-- 児童情報 + QR --}}
            <div class="mt-5 rounded-xl border p-4">
                <div class="text-sm font-semibold text-gray-800 mb-3">児童情報（入退出用QR）</div>

                <div class="space-y-4">
                    @foreach($children as $idx => $c)
                        @php
                            $code = $c['child_code'] ?? null;
                            $qrText = $code ? 'CHILD:'.$code : null;
                            $qrWrapId = 'qrWrap_'.$idx;
                            $qrCanvasId = 'qrCanvas_'.$idx;
                        @endphp

                        <div class="rounded-lg bg-white border p-4">
                            <div class="text-base font-semibold text-gray-900">
                                {{ $c['name'] }}（ID: {{ $code ?? '—' }}）
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $c['grade'] ?? '—' }}年 / {{ $c['school'] ?? '—' }}
                            </div>
                            <div class="text-sm text-gray-600">
                                拠点：{{ $c['base'] ?? '—' }}
                            </div>
                            @if(!empty($c['relationship']))
                                <div class="text-sm text-gray-600">
                                    続柄：{{ $c['relationship'] }}
                                </div>
                            @endif

                            <div class="mt-4 border-t pt-4">
                                <div class="text-sm font-semibold text-gray-800 mb-2">入退出用QRコード</div>

                                <div class="text-xs text-gray-600 mb-3">
                                    入退出の時に使用します。スクショしてご利用できます。
                                </div>

                                @if($qrText)
                                    <div class="flex flex-col items-center gap-3">
                                        {{-- QR描画エリア（canvas） --}}
                                        <div class="rounded-xl border bg-gray-50 p-3">
                                            <canvas id="{{ $qrCanvasId }}" width="220" height="220"
                                                    class="block"></canvas>
                                        </div>

                                        {{-- ダウンロードボタン（download.gif） --}}
                                        <button type="button"
                                                class="group inline-flex flex-col items-center gap-1"
                                                onclick="downloadQrPng('{{ $qrCanvasId }}', '{{ $c['name'] ?? 'child' }}', '{{ $code }}')">
                                            <span class="inline-flex items-center justify-center w-14 h-14 rounded-full
                                                         bg-white border border-gray-200 shadow-sm
                                                         transition-all duration-200
                                                         group-hover:bg-gray-50 group-hover:-translate-y-0.5 group-hover:shadow
                                                         focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                                                <img src="{{ asset('images/download.gif') }}" alt="download" class="w-9 h-9 object-contain">
                                            </span>
                                            <span class="text-sm font-semibold text-gray-900">ダウンロード</span>
                                        </button>

                                        <div class="text-xs text-gray-500">
                                            ※ QR画像だけを保存できます（PNG）
                                        </div>

                                        {{-- デバッグ/確認用（必要なら消してOK） --}}
                                        <div class="text-[11px] text-gray-400">
                                            QR: {{ $qrText }}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm text-rose-700">
                                        児童ID（child_code）が未設定のためQRを生成できません。スタッフへご連絡ください。
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- QR文字列をJSへ渡す --}}
                        @if($qrText)
                            <input type="hidden" id="{{ $qrWrapId }}" value="{{ $qrText }}">
                        @endif
                    @endforeach
                </div>

                <p class="text-xs text-gray-500 mt-4">
                    ※このページは安全のため、共有URLの期限が切れると閲覧できなくなります。
                </p>
            </div>
        </div>
    </div>

    {{-- QR描画（依存なし・軽量） --}}
    <script>
        /**
         * QRを “簡易” に描画するための超軽量実装。
         * 本格的なQRライブラリ（qrcode.js 等）を使うのが理想ですが、
         * まずは「確実に動く」ために CDN を使います。
         */
    </script>

    {{-- qrcode-generator（軽量・canvas描画が簡単） --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>

    <script>
        function drawQrToCanvas(text, canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            // QR生成
            const qr = qrcode(0, 'M'); // typeNumber=0（自動）
            qr.addData(text);
            qr.make();

            const ctx = canvas.getContext('2d');
            const size = canvas.width; // 正方形
            const cells = qr.getModuleCount();

            // 余白（quiet zone）
            const quiet = 8; // ピクセル
            ctx.clearRect(0, 0, size, size);

            // 背景白
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, size, size);

            const drawable = size - quiet * 2;
            const tileW = drawable / cells;

            // 黒セル描画
            ctx.fillStyle = '#000000';
            for (let r = 0; r < cells; r++) {
                for (let c = 0; c < cells; c++) {
                    if (qr.isDark(r, c)) {
                        const x = Math.floor(quiet + c * tileW);
                        const y = Math.floor(quiet + r * tileW);
                        const w = Math.ceil(tileW);
                        const h = Math.ceil(tileW);
                        ctx.fillRect(x, y, w, h);
                    }
                }
            }
        }

        function safeFilePart(s) {
            return String(s ?? '')
                .replace(/[\\\/:*?"<>|]/g, '_')
                .replace(/\s+/g, '_')
                .slice(0, 40);
        }

        function downloadQrPng(canvasId, childName, childCode) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const a = document.createElement('a');
            const namePart = safeFilePart(childName);
            const codePart = safeFilePart(childCode);
            a.download = `QR_${codePart || 'child'}_${namePart || ''}.png`.replace(/_+\.png$/, '.png');
            a.href = canvas.toDataURL('image/png');
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

        // ページロード時に全QRを描画
        document.addEventListener('DOMContentLoaded', () => {
            // hidden input: qrWrap_{idx} / canvas: qrCanvas_{idx}
            const inputs = document.querySelectorAll('input[id^="qrWrap_"]');
            inputs.forEach((inp) => {
                const idx = inp.id.replace('qrWrap_', '');
                const text = inp.value;
                const canvasId = `qrCanvas_${idx}`;
                if (text) drawQrToCanvas(text, canvasId);
            });
        });
    </script>
</x-guest-layout>
