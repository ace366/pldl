<x-guest-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h1 class="text-lg font-semibold text-gray-800 mb-2">入力内容の確認</h1>
                <p class="text-sm text-gray-600 mb-6">
                    内容をご確認のうえ、「この内容で登録する」を押してください。
                </p>

                @php
                    // みどり市想定：表示用に電話番号をハイフン整形（保存は数字のみ）
                    $rawPhone = $data['guardian']['phone'] ?? null;
                    $rawEmergencyPhone = $data['guardian']['emergency_phone'] ?? null;

                    $formatPhone = function ($digits) {
                        $digits = preg_replace('/\D+/', '', (string)$digits);
                        if ($digits === '') return null;

                        // 携帯 070/080/090 (11桁)
                        if (preg_match('/^(070|080|090)\d{8}$/', $digits)) {
                            return substr($digits, 0, 3) . '-' . substr($digits, 3, 4) . '-' . substr($digits, 7, 4);
                        }

                        // 固定 0277 (10 or 11桁)
                        if (str_starts_with($digits, '0277')) {
                            if (strlen($digits) === 10) {
                                // 0277-xx-xxxx
                                return substr($digits, 0, 4) . '-' . substr($digits, 4, 2) . '-' . substr($digits, 6, 4);
                            }
                            if (strlen($digits) === 11) {
                                // 0277-xxx-xxxx
                                return substr($digits, 0, 4) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7, 4);
                            }
                        }

                        // fallback
                        return $digits;
                    };

                    $phoneDisp = $formatPhone($rawPhone);
                    $emergencyPhoneDisp = $formatPhone($rawEmergencyPhone);

                    $birthDisp = $data['child']['birth_date'] ?? null;
                    if (!empty($birthDisp)) {
                        // YYYY-MM-DD -> YYYY年MM月DD日 っぽく
                        try {
                            $dt = \Carbon\Carbon::parse($birthDisp);
                            $birthDisp = $dt->format('Y年n月j日');
                        } catch (\Exception $e) {
                            // そのまま表示
                        }
                    }

                    $hasAllergy = (string)($data['child']['has_allergy'] ?? '0');
                    $allergyLabel = ($hasAllergy === '1') ? '有' : '無';
                    $allergyNote  = $data['child']['allergy_note'] ?? null;
                @endphp

                <div class="space-y-6">
                    {{-- お子さま --}}
                    <div class="rounded-lg border p-4 bg-gray-50">
                        <div class="font-semibold text-gray-800 mb-3">お子さま</div>
                        <div class="text-sm text-gray-800 space-y-1">
                            <div>氏名：{{ $data['child']['last_name'] }} {{ $data['child']['first_name'] }}</div>
                            <div>ふりがな：{{ $data['child']['last_name_kana'] ?? '—' }} {{ $data['child']['first_name_kana'] ?? '—' }}</div>

                            {{-- ★追加：学年/生年月日 --}}
                            <div>学年：{{ $data['child']['grade'] }}年</div>
                            <div>生年月日：{{ $birthDisp ?? '—' }}</div>

                            <div>学校：{{ $schoolName ?? '—' }}</div>
                            <div>拠点：{{ $baseName ?? '—' }}</div>

                            {{-- ★追加：アレルギー --}}
                            <div>アレルギー：{{ $allergyLabel }}</div>
                            <div>
                                アレルギー内容：
                                @if($hasAllergy === '1')
                                    {{ $allergyNote ?: '—' }}
                                @else
                                    —
                                @endif
                            </div>

                            <div>備考：{{ $data['child']['note'] ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- 保護者さま --}}
                    <div class="rounded-lg border p-4 bg-gray-50">
                        <div class="font-semibold text-gray-800 mb-3">保護者さま</div>
                        <div class="text-sm text-gray-800 space-y-1">
                            <div>氏名：{{ $data['guardian']['last_name'] }} {{ $data['guardian']['first_name'] }}</div>
                            <div>ふりがな：{{ $data['guardian']['last_name_kana'] ?? '—' }} {{ $data['guardian']['first_name_kana'] ?? '—' }}</div>
                            <div>メール：{{ $data['guardian']['email'] ?? '—' }}</div>
                            <div>電話：{{ $phoneDisp ?? '—' }}</div>
                            <div>緊急連絡先：{{ $emergencyPhoneDisp ?? '—' }}</div>
                            <div>LINE userId：{{ $data['guardian']['line_user_id'] ?? '—' }}</div>
                            <div>優先連絡：{{ $data['guardian']['preferred_contact'] ?? '未設定（自動選択）' }}</div>
                        </div>
                    </div>

                    {{-- 続柄 --}}
                    <div class="rounded-lg border p-4 bg-gray-50">
                        <div class="font-semibold text-gray-800 mb-2">続柄</div>
                        <div class="text-sm text-gray-800">
                            {{ $data['link']['relationship'] ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <a href="{{ route('enroll.create') }}"
                       class="inline-flex items-center gap-2 text-sm text-gray-700 underline hover:text-gray-900">
                        ← 入力を修正する
                    </a>

                    <form method="POST" action="{{ route('enroll.store', [], false) }}">
                        @csrf

                        {{-- ✅ confirm → store へセッション無しで渡す（hidden） --}}
                        @foreach(($data['child'] ?? []) as $k => $v)
                            <input type="hidden" name="child[{{ $k }}]" value="{{ $v }}">
                        @endforeach

                        @foreach(($data['guardian'] ?? []) as $k => $v)
                            <input type="hidden" name="guardian[{{ $k }}]" value="{{ $v }}">
                        @endforeach

                        @foreach(($data['link'] ?? []) as $k => $v)
                            <input type="hidden" name="link[{{ $k }}]" value="{{ $v }}">
                        @endforeach

                        {{-- ★改善：目立つ登録ボタン（アイコン付き / グラデ / 影 / フォーカス） --}}
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl
                                       bg-gradient-to-r from-indigo-600 to-violet-600
                                       text-white text-sm font-extrabold shadow-lg
                                       hover:from-indigo-700 hover:to-violet-700
                                       focus:outline-none focus:ring-4 focus:ring-indigo-200">
                            <span aria-hidden="true">✅</span>
                            登録する
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>
