<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-semibold text-gray-800">
                        児童・保護者 一括新規登録
                    </h1>

                    <a href="{{ route('admin.children.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        児童一覧へ
                    </a>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-rose-50 p-3 text-rose-800 text-sm">
                        入力内容にエラーがあります。赤枠の項目を確認してください。
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.enrollments.child_guardian.store') }}" id="enrollForm" class="space-y-6">
                    @csrf

                    {{-- ========== 児童 ========== --}}
                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-semibold text-gray-800 mb-3">【質問】児童の情報を入力してください</div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">児童ID（4桁・数字）※未入力なら自動採番</label>
                                <input type="text" name="child[child_code]" inputmode="numeric" maxlength="4"
                                    value="{{ old('child.child_code') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="例：0123">
                                @error('child.child_code')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-gray-500">小学生でも覚えやすい4桁。未入力なら自動でユニーク採番します。</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">状態</label>
                                <select name="child[status]"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="enrolled" @selected(old('child.status','enrolled')==='enrolled')>在籍</option>
                                    <option value="withdrawn" @selected(old('child.status')==='withdrawn')>退会</option>
                                </select>
                                @error('child.status')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                                <input type="text" name="child[last_name]" value="{{ old('child.last_name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('child.last_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                                <input type="text" name="child[first_name]" value="{{ old('child.first_name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('child.first_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                                <input type="text" name="child[last_name_kana]" value="{{ old('child.last_name_kana') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="ひらがな">
                                @error('child.last_name_kana')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                                <input type="text" name="child[first_name_kana]" value="{{ old('child.first_name_kana') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="ひらがな">
                                @error('child.first_name_kana')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">学年</label>
                                <select name="child[grade]" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @for($i=1;$i<=6;$i++)
                                        <option value="{{ $i }}" @selected((string)old('child.grade')===(string)$i)>{{ $i }}年</option>
                                    @endfor
                                </select>
                                @error('child.grade')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">拠点（任意）</label>
                                <select name="child[base_id]"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">未設定</option>
                                    @foreach($bases as $b)
                                        <option value="{{ $b->id }}" @selected((string)old('child.base_id')===(string)$b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                @error('child.base_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-gray-500">※ children.base（文字列）は選択した拠点名で自動保存します。</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">学校（任意）</label>
                            <select name="child[school_id]"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">未設定</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" @selected((string)old('child.school_id')===(string)$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('child.school_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">備考（任意）</label>
                            <textarea name="child[note]" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="例：アレルギー、迎えの注意点など">{{ old('child.note') }}</textarea>
                            @error('child.note')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ========== 保護者 ========== --}}
                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-semibold text-gray-800 mb-3">【質問】保護者の情報を入力してください</div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                                <input type="text" name="guardian[last_name]" value="{{ old('guardian.last_name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('guardian.last_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                                <input type="text" name="guardian[first_name]" value="{{ old('guardian.first_name') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('guardian.first_name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                                <input type="text" name="guardian[last_name_kana]" value="{{ old('guardian.last_name_kana') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="ひらがな">
                                @error('guardian.last_name_kana')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                                <input type="text" name="guardian[first_name_kana]" value="{{ old('guardian.first_name_kana') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="ひらがな">
                                @error('guardian.first_name_kana')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">電話（任意・ハイフン無し自動）</label>
                                <input type="text" name="guardian[phone]" id="phoneInput" inputmode="numeric"
                                    value="{{ old('guardian.phone') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="例：09012345678">
                                @error('guardian.phone')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                                <p class="mt-1 text-xs text-gray-500">入力中に自動で数字だけに整形します（サーバ側でも再整形）。</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">メール（任意）</label>
                                <input type="email" name="guardian[email]" value="{{ old('guardian.email') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="例：aaa@example.com">
                                @error('guardian.email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">LINE userId（任意）</label>
                                <input type="text" name="guardian[line_user_id]" value="{{ old('guardian.line_user_id') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="例：Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                @error('guardian.line_user_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">連絡手段の希望（任意）</label>
                                <select name="guardian[preferred_contact]"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">未設定</option>
                                    <option value="phone" @selected(old('guardian.preferred_contact')==='phone')>電話</option>
                                    <option value="email" @selected(old('guardian.preferred_contact')==='email')>メール</option>
                                    <option value="line"  @selected(old('guardian.preferred_contact')==='line')>LINE</option>
                                </select>
                                @error('guardian.preferred_contact')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ========== 紐づけ ========== --}}
                    <div class="rounded-xl border p-4">
                        <div class="text-sm font-semibold text-gray-800 mb-3">【質問】児童との続柄（任意）</div>

                        <div class="max-w-md">
                            <label class="block text-sm font-medium text-gray-700">続柄</label>
                            <input type="text" name="relationship" value="{{ old('relationship') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="例：母 / 父 / 祖母">
                            @error('relationship')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-gray-500">child_guardian.relationship と child_guardian.relation の両方へ同じ値を保存します。</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-semibold
                                   text-indigo-800 bg-indigo-50 border border-indigo-200
                                   shadow-sm transition-all duration-200
                                   hover:bg-indigo-100 hover:-translate-y-0.5 hover:shadow
                                   focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                            一括登録する
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        // 電話は「数字だけ」に自動整形（入力中）
        const phoneInput = document.getElementById('phoneInput');
        if (phoneInput) {
            phoneInput.addEventListener('input', () => {
                phoneInput.value = (phoneInput.value || '').replace(/\D/g, '');
            });
        }

        // 送信前にも保険で整形
        const form = document.getElementById('enrollForm');
        if (form && phoneInput) {
            form.addEventListener('submit', () => {
                phoneInput.value = (phoneInput.value || '').replace(/\D/g, '');
            });
        }
    </script>
</x-app-layout>
