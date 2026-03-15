<x-guest-layout>
@php($schools = $schools ?? collect())
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h1 class="text-lg font-semibold text-gray-800 mb-2">児童＋保護者 新規登録</h1>
                <p class="text-sm text-gray-600 mb-6">
                    質問に答えるだけで、児童と保護者をまとめて登録します。<br>
                    ※電話番号は入力中に自動で「-」が入り、登録時は数字だけで保存されます
                </p>
                <p class="text-sm text-gray-600 bg-amber-50 border border-amber-200 rounded-md px-3 py-2 mb-6">
                    きょうだい登録や保護者の【追加登録】は、アカウント作成後にできます。
                </p>

                <form method="POST" action="{{ route('enroll.confirm', [], false) }}" class="space-y-8">
                    @csrf

                    {{-- 児童情報 --}}
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 mb-4">【質問①】お子さまの情報</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">お子さまの姓（漢字）</label>
                                <input name="child[last_name]" value="{{ old('child.last_name') }}" required
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('child.last_name')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">お子さまの名（漢字）</label>
                                <input name="child[first_name]" value="{{ old('child.first_name') }}" required
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('child.first_name')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">お子さまのせい（ふりがな）</label>
                                <input name="child[last_name_kana]" value="{{ old('child.last_name_kana') }}"
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="ひらがな">
                                @error('child.last_name_kana')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">お子さまのめい（ふりがな）</label>
                                <input name="child[first_name_kana]" value="{{ old('child.first_name_kana') }}"
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="ひらがな">
                                @error('child.first_name_kana')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">学年（小1〜小6）</label>
                                <select id="child_grade" name="child[grade]" required
                                        class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">選択してください</option>
                                    @for($i=1;$i<=6;$i++)
                                        <option value="{{ $i }}" @selected((string)old('child.grade')===(string)$i)>{{ $i }}年</option>
                                    @endfor
                                </select>
                                @error('child.grade')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">学校</label>
                                <select name="child[school_id]" required
                                        class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">選択してください</option>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}" @selected((string)old('child.school_id')===(string)$s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('child.school_id')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">生年月日（必須）</label>
                                <input id="child_birth_date" type="date" name="child[birth_date]" value="{{ old('child.birth_date') }}" required
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('child.birth_date')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- 拠点：必須に変更 --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">拠点（必須）</label>
                            <select name="child[base_id]" required
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">選択してください</option>
                                @foreach($bases as $b)
                                    <option value="{{ $b->id }}" @selected((string)old('child.base_id')===(string)$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('child.base_id')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                        </div>

                        {{-- アレルギー：有無 + 有のときだけ内容 --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">アレルギー</label>

                            @php($hasAllergyOld = old('child.has_allergy', '0'))
                            <div class="mt-2 flex items-center gap-6">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="child[has_allergy]" value="0"
                                           class="text-indigo-600 focus:ring-indigo-500"
                                           @checked((string)$hasAllergyOld === '0')>
                                    <span class="text-sm text-gray-700">無</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="child[has_allergy]" value="1"
                                           class="text-indigo-600 focus:ring-indigo-500"
                                           @checked((string)$hasAllergyOld === '1')>
                                    <span class="text-sm text-gray-700">有</span>
                                </label>
                            </div>

                            @error('child.has_allergy')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror

                            <div id="allergyDetailWrap" class="mt-3 hidden">
                                <label class="block text-sm font-medium text-gray-700">アレルギー内容（「有」の場合は必須）</label>
                                <textarea id="allergyDetail" name="child[allergy_note]" rows="3"
                                          class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="例：卵、乳、小麦、エビなど">{{ old('child.allergy_note') }}</textarea>
                                @error('child.allergy_note')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-gray-500">※ 可能な範囲で具体的にご記入ください</p>
                            </div>
                        </div>

                        {{-- 備考（アレルギー以外） --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">備考（任意）</label>
                            <textarea name="child[note]" rows="3"
                                      class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="例：連絡事項など（アレルギー以外）">{{ old('child.note') }}</textarea>
                            @error('child.note')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- 保護者情報 --}}
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 mb-4">【質問②】保護者さまの情報</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">保護者さまの姓（漢字）</label>
                                <input name="guardian[last_name]" value="{{ old('guardian.last_name') }}" required
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('guardian.last_name')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">保護者さまの名（漢字）</label>
                                <input name="guardian[first_name]" value="{{ old('guardian.first_name') }}" required
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('guardian.first_name')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                                <input name="guardian[last_name_kana]" value="{{ old('guardian.last_name_kana') }}"
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="ひらがな">
                                @error('guardian.last_name_kana')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                                <input name="guardian[first_name_kana]" value="{{ old('guardian.first_name_kana') }}"
                                       class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="ひらがな">
                                @error('guardian.first_name_kana')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">メール（必須）</label>
                            <input type="email" name="guardian[email]" value="{{ old('guardian.email') }}" required
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="例：parent@example.com">
                            @error('guardian.email')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                        </div>

                        {{-- 電話：表示=ハイフン / 送信=数字のみ --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">電話（必須）</label>

                            {{-- 送信用（数字のみ） --}}
                            <input type="hidden" id="phoneHidden" name="guardian[phone]" value="{{ old('guardian.phone') }}">

                            {{-- 表示用（ハイフン入り） --}}
                            <input id="phoneDisplay" required
                                   type="tel"
                                   inputmode="numeric"
                                   autocomplete="tel"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="例：090-1234-5678">

                            @error('guardian.phone')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-500">※ 入力は数字だけでOK。自動で「-」が入ります</p>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">緊急連絡先（任意）</label>

                            <input type="hidden" id="emergencyPhoneHidden" name="guardian[emergency_phone]" value="{{ old('guardian.emergency_phone') }}">

                            <input id="emergencyPhoneDisplay"
                                   type="tel"
                                   inputmode="numeric"
                                   autocomplete="tel"
                                   class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="例：080-1234-5678">

                            @error('guardian.emergency_phone')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-500">※ 入力は数字だけでOK。自動で「-」が入ります</p>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">優先連絡手段</label>
                            <select name="guardian[preferred_contact]"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">未設定</option>
                                <option value="email" @selected(old('guardian.preferred_contact')==='email')>メール</option>
                                <option value="phone" @selected(old('guardian.preferred_contact')==='phone')>電話</option>
                            </select>
                            @error('guardian.preferred_contact')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-500">※ メール/電話 のいずれか1つは必須です</p>
                        </div>
                    </div>

                    {{-- 続柄 --}}
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 mb-4">【質問③】お子さまとの続柄（任意）</h2>
                        <input name="link[relationship]" value="{{ old('link.relationship') }}"
                               class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="例：母 / 父 / 祖母">
                        @error('link.relationship')<p class="mt-2 text-sm text-rose-700 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            入力内容を確認する
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ---------- 電話：表示=ハイフン / 送信=数字のみ ----------
        function formatJapanPhoneDisplay(digits) {
            // digits: 数字のみ
            if (!digits) return '';

            // 携帯：070/080/090 => 3-4-4（最大11桁）
            if (digits.startsWith('070') || digits.startsWith('080') || digits.startsWith('090')) {
                const d = digits.slice(0, 11);
                if (d.length <= 3) return d;
                if (d.length <= 7) return d.slice(0, 3) + '-' + d.slice(3);
                return d.slice(0, 3) + '-' + d.slice(3, 7) + '-' + d.slice(7);
            }

            // 固定：0277（みどり市/桐生周辺を想定）
            if (digits.startsWith('0277')) {
                const d = digits.slice(0, 11); // 固定は10 or 11桁想定

                if (d.length <= 4) return d;

                // 10桁: 0277-xx-xxxx（4 + 2 + 4）
                if (d.length <= 10) {
                    // 0277 + 2桁（市内） + 4桁（加入者）
                    // 途中入力も自然に見えるように段階整形
                    if (d.length <= 6) return d.slice(0, 4) + '-' + d.slice(4);
                    return d.slice(0, 4) + '-' + d.slice(4, 6) + '-' + d.slice(6);
                }

                // 11桁: 0277-xxx-xxxx（4 + 3 + 4）
                if (d.length <= 7) return d.slice(0, 4) + '-' + d.slice(4);
                return d.slice(0, 4) + '-' + d.slice(4, 7) + '-' + d.slice(7);
            }

            // それ以外（保険）：数字だけ（または簡易 3-4-4 っぽく）
            // ここは運用で「固定は0277のみ」の前提なら、digitsだけ返すのが安全
            return digits;
        }


        function bindPhoneFormatter(hiddenId, displayId) {
            const hidden = document.getElementById(hiddenId);
            const display = document.getElementById(displayId);

            if (!hidden || !display) return;

            const initialDigits = (hidden.value || '').replace(/[^\d]/g, '').slice(0, 11);
            hidden.value = initialDigits;
            display.value = formatJapanPhoneDisplay(initialDigits);

            display.addEventListener('input', () => {
                const digits = (display.value || '').replace(/[^\d]/g, '').slice(0, 11);
                hidden.value = digits;
                display.value = formatJapanPhoneDisplay(digits);
            });
        }

        bindPhoneFormatter('phoneHidden', 'phoneDisplay');
        bindPhoneFormatter('emergencyPhoneHidden', 'emergencyPhoneDisplay');

        // ---------- アレルギー：有の時だけ表示＆必須 ----------
        const allergyWrap = document.getElementById('allergyDetailWrap');
        const allergyText = document.getElementById('allergyDetail');

        function syncAllergyUI() {
            const checked = document.querySelector('input[name="child[has_allergy]"]:checked');
            const has = checked ? checked.value === '1' : false;

            if (allergyWrap) {
                allergyWrap.classList.toggle('hidden', !has);
            }
            if (allergyText) {
                allergyText.required = has;
            }
        }

        document.querySelectorAll('input[name="child[has_allergy]"]').forEach(el => {
            el.addEventListener('change', syncAllergyUI);
        });

        // 初期化（old()反映後）
        syncAllergyUI();
        // -------------------------
        // 学年→生年月日 自動推定（みどり市/日本の小学校：4月始まり）
        // -------------------------
        const gradeEl = document.getElementById('child_grade');
        const birthEl = document.getElementById('child_birth_date');

        function getSchoolYear(today = new Date()) {
            // 4月始まり：1〜3月は前年が年度
            const y = today.getFullYear();
            const m = today.getMonth() + 1;
            return (m < 4) ? (y - 1) : y;
        }

        function pad2(n) {
            return String(n).padStart(2, '0');
        }

        function setBirthDateByGradeIfEmpty() {
            if (!gradeEl || !birthEl) return;

            const g = parseInt(gradeEl.value, 10);
            if (!g || g < 1 || g > 6) return;

            // 既に入力されてるなら上書きしない（事故防止）
            if ((birthEl.value || '').trim() !== '') return;

            const schoolYear = getSchoolYear(new Date());

            // 目安の誕生年：
            // 小1（2025年度）なら 2018-04-02〜2019-04-01 が多い → 年だけ合わせる用途なので 2018 を採用
            const birthYear = schoolYear - (g + 6);

            // 年だけ合わせたいので、日付はとりあえず 7/1（夏休み前で覚えやすい）
            const defaultDate = `${birthYear}-${pad2(7)}-${pad2(1)}`;
            birthEl.value = defaultDate;
        }

        if (gradeEl && birthEl) {
            // 初回：学年が既に選択済み & 生年月日が空ならセット
            setBirthDateByGradeIfEmpty();

            // 学年変更時：生年月日が空ならセット
            gradeEl.addEventListener('change', setBirthDateByGradeIfEmpty);
        }
    </script>
</x-guest-layout>
