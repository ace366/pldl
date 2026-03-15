<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center gap-3">
                <img src="{{ asset('images/parents.png') }}"
                     alt="保護者"
                     class="w-10 h-10 object-contain">
                <h1 class="text-xl font-semibold text-gray-800">保護者を追加</h1>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.guardians.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                            <input type="text" name="last_name_kana" value="{{ old('last_name_kana') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="ひらがな">
                            @error('last_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                            <input type="text" name="first_name_kana" value="{{ old('first_name_kana') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="ひらがな">
                            @error('first_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">メール</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：parent@example.com">
                        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">電話（自動でハイフン）</label>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            inputmode="numeric"
                            autocomplete="tel"
                            id="phoneInput"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="例：090-1234-5678 / 042-111-1111">
                        @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">※ 数字のみ入力でもOK。自動で 090-****-**** / 0xx-***-**** 形式に整形します。</p>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">LINE userId</label>
                        <input type="text" name="line_user_id" value="{{ old('line_user_id') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                        @error('line_user_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">優先連絡手段</label>
                        <select name="preferred_contact"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">未設定</option>
                            <option value="line"  @selected(old('preferred_contact')==='line')>LINE</option>
                            <option value="email" @selected(old('preferred_contact')==='email')>メール</option>
                            <option value="phone" @selected(old('preferred_contact')==='phone')>電話</option>
                        </select>
                        @error('preferred_contact')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">※ メール/電話/LINE のいずれか1つは必須です。</p>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('admin.guardians.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">戻る</a>

                        <button type="submit" class="group inline-flex flex-col items-center gap-1">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                    bg-gray-50 border border-gray-200 shadow-sm
                                    transition-all duration-200
                                    group-hover:bg-gray-100 group-hover:-translate-y-0.5 group-hover:shadow
                                    focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2">
                                <img src="{{ asset('images/add_parents.png') }}" alt="登録する" class="w-7 h-7 object-contain">
                            </span>
                            <span class="text-sm font-semibold text-gray-900">登録する</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- 電話：数字のみ入力 → 自動ハイフン整形 --}}
    <script>
        (function () {
            const el = document.getElementById('phoneInput');
            if (!el) return;

            const digitsOnly = (v) => (v || '').replace(/[^\d]/g, '');

            // 0 から始まる日本の電話番号を想定してハイフン整形（実用寄り）
            function formatJpPhone(rawDigits) {
                const d = digitsOnly(rawDigits);
                if (!d) return '';

                // 携帯：070/080/090 + 8桁
                if (/^0[789]0/.test(d)) {
                    const a = d.slice(0, 3);
                    const b = d.slice(3, 7);
                    const c = d.slice(7, 11);
                    return [a, b, c].filter(Boolean).join('-');
                }

                // 0120 / 0800（フリーダイヤル系）: 4-3-3 目安
                if (/^0(120|800)/.test(d)) {
                    const a = d.slice(0, 4);
                    const b = d.slice(4, 7);
                    const c = d.slice(7, 10);
                    return [a, b, c].filter(Boolean).join('-');
                }

                // 固定電話：10桁想定（0 + 9桁）
                // 市外局番の長さが 2〜5桁程度なので、ざっくり以下に寄せる
                // 0x + 4 + 4（例: 03-1234-5678）
                if (d.length <= 10) {
                    // 03/06 は 2桁市外局番
                    if (/^0[36]/.test(d)) {
                        const a = d.slice(0, 2);
                        const b = d.slice(2, 6);
                        const c = d.slice(6, 10);
                        return [a, b, c].filter(Boolean).join('-');
                    }
                    // 0xx は 3桁市外局番（例: 042-123-4567 or 048-123-4567）
                    const a = d.slice(0, 3);
                    // 10桁なら 3-3-4、9桁以下なら埋まる範囲で
                    const b = d.slice(3, 6);
                    const c = d.slice(6, 10);
                    return [a, b, c].filter(Boolean).join('-');
                }

                // 11桁以上（入力途中など）：携帯以外は末尾を切って表示
                return d.slice(0, 11);
            }

            // 入力中：数字以外を除外して整形
            el.addEventListener('input', () => {
                const cursor = el.selectionStart;
                const before = el.value;
                el.value = formatJpPhone(before);

                // ざっくり：末尾入力想定でカーソルを末尾へ寄せる
                // (細かいカーソル制御が必要なら次で調整します)
                if (cursor != null) el.setSelectionRange(el.value.length, el.value.length);
            });

            // 貼り付け時も数字だけに
            el.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                el.value = formatJpPhone(text);
            });

            // 送信前：必ず整形してから送る（数字のみで保存したい場合はControllerでdigitsOnly推奨）
            const form = el.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    el.value = formatJpPhone(el.value);
                });
            }
        })();
    </script>
</x-app-layout>
