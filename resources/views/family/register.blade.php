<x-guest-layout>
    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-lg font-semibold text-gray-800">児童・保護者 新規登録</h1>
            <p class="text-sm text-gray-600 mt-1">
                1ページで児童と保護者をまとめて登録します。
            </p>

            @if (session('success'))
                <div class="mt-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('guardian.contact'))
                <div class="mt-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    {{ $errors->first('guardian.contact') }}
                </div>
            @endif

            <form method="POST" action="{{ route('family.register.store', [], false) }}" class="mt-6 space-y-6">
                @csrf

                {{-- 児童 --}}
                <div class="rounded-lg border p-4">
                    <div class="font-semibold text-gray-800 mb-3">児童情報</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                            <input name="child[last_name]" value="{{ old('child.last_name') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('child.last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                            <input name="child[first_name]" value="{{ old('child.first_name') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('child.first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                            <input name="child[last_name_kana]" value="{{ old('child.last_name_kana') }}"
                                   placeholder="ひらがな"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('child.last_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                            <input name="child[first_name_kana]" value="{{ old('child.first_name_kana') }}"
                                   placeholder="ひらがな"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('child.first_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ★追加：生年月日 --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">生年月日</label>
                            <input type="date" name="child[birth_date]" value="{{ old('child.birth_date') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('child.birth_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="hidden sm:block"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">学年</label>
                            <select name="child[grade]" required
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @for($i=1;$i<=6;$i++)
                                    <option value="{{ $i }}" @selected(old('child.grade')==(string)$i)>{{ $i }}年</option>
                                @endfor
                            </select>
                            @error('child.grade')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">学校</label>
                            <select name="child[school_id]" required
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">選択してください</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" @selected(old('child.school_id')==(string)$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('child.school_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">拠点（任意）</label>
                            <select name="child[base_id]"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">未設定</option>
                                @foreach($bases as $b)
                                    <option value="{{ $b->id }}" @selected(old('child.base_id')==(string)$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('child.base_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">状態</label>
                            <select name="child[status]" required
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="enrolled" @selected(old('child.status','enrolled')==='enrolled')>在籍</option>
                                <option value="withdrawn" @selected(old('child.status')==='withdrawn')>退会</option>
                            </select>
                            @error('child.status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">児童ID（4桁）</label>
                            <input id="child_code" name="child[child_code]" value="{{ old('child.child_code') }}" required
                                   inputmode="numeric" maxlength="4" placeholder="例：1234"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">※ 4桁の数字（重複不可）</p>
                            @error('child.child_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">備考（任意）</label>
                            <input name="child[note]" value="{{ old('child.note') }}"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="例：アレルギー等">
                            @error('child.note')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- 保護者 --}}
                <div class="rounded-lg border p-4">
                    <div class="font-semibold text-gray-800 mb-3">保護者情報</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                            <input name="guardian[last_name]" value="{{ old('guardian.last_name') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guardian.last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                            <input name="guardian[first_name]" value="{{ old('guardian.first_name') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guardian.first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                            <input name="guardian[last_name_kana]" value="{{ old('guardian.last_name_kana') }}"
                                   placeholder="ひらがな"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guardian.last_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                            <input name="guardian[first_name_kana]" value="{{ old('guardian.first_name_kana') }}"
                                   placeholder="ひらがな"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('guardian.first_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">続柄（任意）</label>
                        <input name="relationship" value="{{ old('relationship') }}"
                               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：母 / 父 / 祖母">
                        @error('relationship')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">メール（任意）</label>
                        <input type="email" name="guardian[email]" value="{{ old('guardian.email') }}"
                               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：parent@example.com">
                        @error('guardian.email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- ★修正：電話は「表示=ハイフン」「送信=数字のみ」 --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">電話（任意）</label>

                        {{-- 送信用（数字のみ） --}}
                        <input type="hidden" id="guardian_phone" name="guardian[phone]" value="{{ old('guardian.phone') }}">

                        {{-- 表示用（ハイフン入り） --}}
                        <input
                            id="guardian_phone_display"
                            type="tel"
                            inputmode="numeric"
                            autocomplete="tel"
                            class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="例：090-1234-5678"
                        >

                        <p class="mt-1 text-xs text-gray-500">※ 入力は数字だけでOK。自動で「-」が入ります。</p>
                        @error('guardian.phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">LINE userId（任意）</label>
                        <input name="guardian[line_user_id]" value="{{ old('guardian.line_user_id') }}"
                               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：Uxxxxxxxxxxxxxxxxxxxx">
                        @error('guardian.line_user_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">優先連絡手段（任意）</label>
                        <select name="guardian[preferred_contact]"
                                class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">未設定</option>
                            <option value="line"  @selected(old('guardian.preferred_contact')==='line')>LINE</option>
                            <option value="email" @selected(old('guardian.preferred_contact')==='email')>メール</option>
                            <option value="phone" @selected(old('guardian.preferred_contact')==='phone')>電話</option>
                        </select>
                        @error('guardian.preferred_contact')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">※ メール/電話/LINE のいずれか1つは必須です。</p>
                    </div>
                </div>

                {{-- 送信 --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                        登録する
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 児童IDは数字のみ4桁
        const childCode = document.getElementById('child_code');
        if (childCode) {
            childCode.addEventListener('input', () => {
                childCode.value = childCode.value.replace(/[^\d]/g, '').slice(0, 4);
            });
        }

        // 日本の電話番号っぽくハイフン整形（表示用）＋数字だけをhiddenへ格納
        function formatJapanPhoneDisplay(digits) {
            // digits: 数字のみ
            if (!digits) return '';

            // 0120-xxx-xxx（10桁）
            if (digits.startsWith('0120')) {
                if (digits.length <= 4) return digits;
                if (digits.length <= 7) return digits.slice(0,4) + '-' + digits.slice(4);
                return digits.slice(0,4) + '-' + digits.slice(4,7) + '-' + digits.slice(7,10);
            }

            // 03 / 06: 2-4-4（10桁）
            if (digits.startsWith('03') || digits.startsWith('06')) {
                if (digits.length <= 2) return digits;
                if (digits.length <= 6) return digits.slice(0,2) + '-' + digits.slice(2);
                return digits.slice(0,2) + '-' + digits.slice(2,6) + '-' + digits.slice(6,10);
            }

            // 携帯(070/080/090)・IP電話(050): 3-4-4（11桁想定）
            if (digits.startsWith('070') || digits.startsWith('080') || digits.startsWith('090') || digits.startsWith('050')) {
                if (digits.length <= 3) return digits;
                if (digits.length <= 7) return digits.slice(0,3) + '-' + digits.slice(3);
                return digits.slice(0,3) + '-' + digits.slice(3,7) + '-' + digits.slice(7,11);
            }

            // その他はとりあえず 3-3-4（10桁）っぽく
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return digits.slice(0,3) + '-' + digits.slice(3);
            return digits.slice(0,3) + '-' + digits.slice(3,6) + '-' + digits.slice(6,10);
        }

        // 電話：表示(input)はハイフン、送信(hidden)は数字のみ
        const phoneHidden  = document.getElementById('guardian_phone');
        const phoneDisplay = document.getElementById('guardian_phone_display');

        if (phoneHidden && phoneDisplay) {
            // 初期表示（oldが数字のみ or ハイフン入りでもOK）
            const initialDigits = (phoneHidden.value || '').replace(/[^\d]/g, '').slice(0, 11);
            phoneHidden.value = initialDigits;
            phoneDisplay.value = formatJapanPhoneDisplay(initialDigits);

            phoneDisplay.addEventListener('input', () => {
                const digits = phoneDisplay.value.replace(/[^\d]/g, '').slice(0, 11);
                phoneHidden.value = digits;
                phoneDisplay.value = formatJapanPhoneDisplay(digits);
            });
        }
    </script>
</x-guest-layout>
