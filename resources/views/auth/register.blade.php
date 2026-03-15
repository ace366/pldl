<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" id="registerForm">
        @csrf

        <div class="text-center mb-4">
            <div class="text-lg font-semibold text-gray-800">新規登録</div>
            <div class="text-sm text-gray-600">必要事項を入力してください</div>
        </div>

        {{-- 氏名（漢字） --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="last_name" value="姓（漢字）" />
                <x-text-input id="last_name" class="block mt-1 w-full"
                    type="text" name="last_name" value="{{ old('last_name') }}"
                    required autofocus autocomplete="family-name"
                    inputmode="text" lang="ja" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="first_name" value="名（漢字）" />
                <x-text-input id="first_name" class="block mt-1 w-full"
                    type="text" name="first_name" value="{{ old('first_name') }}"
                    required autocomplete="given-name"
                    inputmode="text" lang="ja" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
        </div>

        {{-- ふりがな --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <x-input-label for="last_name_kana" value="せい（ふりがな）" />
                <x-text-input id="last_name_kana" class="block mt-1 w-full"
                    type="text" name="last_name_kana" value="{{ old('last_name_kana') }}"
                    required autocomplete="off"
                    inputmode="text" lang="ja" placeholder="ひらがな"
                    autocapitalize="off" autocorrect="off" spellcheck="false" />
                <x-input-error :messages="$errors->get('last_name_kana')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="first_name_kana" value="めい（ふりがな）" />
                <x-text-input id="first_name_kana" class="block mt-1 w-full"
                    type="text" name="first_name_kana" value="{{ old('first_name_kana') }}"
                    required autocomplete="off"
                    inputmode="text" lang="ja" placeholder="ひらがな"
                    autocapitalize="off" autocorrect="off" spellcheck="false" />
                <x-input-error :messages="$errors->get('first_name_kana')" class="mt-2" />
            </div>
        </div>

        {{-- 電話番号（数字入力 + 自動ハイフン） --}}
        <div class="mt-4">
            <x-input-label for="phone" value="電話番号（緊急連絡用）" />
            <x-text-input id="phone" class="block mt-1 w-full"
                type="tel" name="phone" value="{{ old('phone') }}"
                required autocomplete="tel" inputmode="numeric"
                placeholder="09000000000 / 0480000000" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">数字のみ入力してください（自動でハイフンが入ります）。</p>
        </div>

        {{-- 備考 --}}
        <div class="mt-4">
            <x-input-label for="note" value="備考（配慮事項など）" />
            <textarea id="note" name="note" rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="例：喘息があるため運動後は休憩が必要、など">{{ old('note') }}</textarea>
            <x-input-error :messages="$errors->get('note')" class="mt-2" />
        </div>

        {{-- メール --}}
        <div class="mt-4">
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" class="block mt-1 w-full"
                type="email" name="email" value="{{ old('email') }}"
                required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- パスワード --}}
        <div class="mt-4">
            <x-input-label for="password" value="パスワード" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password" name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">※ 現在は4文字以上（運用に合わせて強化可能）</p>
        </div>

        {{-- パスワード（確認） --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="パスワード（確認）" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password" name="password_confirmation"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}">
                すでに登録済みの方はこちら
            </a>

            <x-primary-button>
                登録する
            </x-primary-button>
        </div>
    </form>

    <script>
    (function () {
        // --- ひらがな入力：IME変換中は触らない（確定後に整形） ---
        const kanaEls = [
            document.getElementById('last_name_kana'),
            document.getElementById('first_name_kana'),
        ].filter(Boolean);

        const toHiragana = (s) => {
            if (!s) return '';
            // カタカナ→ひらがな
            return s.replace(/[\u30A1-\u30F6]/g, ch =>
                String.fromCharCode(ch.charCodeAt(0) - 0x60)
            );
        };

        const normalizeKana = (value) => {
            let v = value || '';
            v = toHiragana(v);

            // ひらがな・長音・空白だけ許可（必要なら「・」等を追加）
            v = v.replace(/[^\u3040-\u309F\u30FC\s]/g, '');

            // 連続空白は1つに
            v = v.replace(/\s+/g, ' ');

            return v;
        };

        kanaEls.forEach(el => {
            let composing = false;

            // 日本語入力向けの属性（保険）
            el.setAttribute('lang', 'ja');
            el.setAttribute('inputmode', 'text');
            el.setAttribute('autocomplete', 'off');
            el.setAttribute('autocapitalize', 'off');
            el.setAttribute('autocorrect', 'off');
            el.setAttribute('spellcheck', 'false');

            // IME変換開始/終了
            el.addEventListener('compositionstart', () => { composing = true; });
            el.addEventListener('compositionend', () => {
                composing = false;
                // 確定した直後に整形（1フレーム遅らせると安定する端末がある）
                requestAnimationFrame(() => {
                    el.value = normalizeKana(el.value);
                });
            });

            // 入力中：変換中は触らない
            el.addEventListener('input', () => {
                if (composing) return;
                el.value = normalizeKana(el.value);
            });

            // フォーカス外れた時も最終整形
            el.addEventListener('blur', () => {
                el.value = normalizeKana(el.value);
            });
        });

        // --- 電話：数字だけ保持して見た目をハイフン整形（現状維持） ---
        const phoneEl = document.getElementById('phone');
        if (phoneEl) {
            const formatPhone = (digits) => {
                if (digits.length === 11) {
                    return digits.replace(/^(\d{3})(\d{4})(\d{4})$/, '$1-$2-$3');
                }
                if (digits.length === 10) {
                    if (/^(03|06)/.test(digits)) {
                        return digits.replace(/^(\d{2})(\d{4})(\d{4})$/, '$1-$2-$3');
                    }
                    return digits.replace(/^(\d{3})(\d{3})(\d{4})$/, '$1-$2-$3');
                }
                return digits;
            };

            phoneEl.addEventListener('input', () => {
                const digits = (phoneEl.value || '').replace(/\D+/g, '').slice(0, 11);
                phoneEl.value = formatPhone(digits);
            });

            phoneEl.setAttribute('inputmode', 'numeric');
            phoneEl.setAttribute('autocomplete', 'tel');
        }
    })();
    </script>

</x-guest-layout>
