{{-- resources/views/admin/enroll/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            児童・保護者 新規登録
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
                    <div class="font-semibold mb-2">入力内容を確認してください</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.enroll.store') }}" id="enrollForm">
                    @csrf

                    {{-- 児童情報 --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3">児童情報</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">氏名</label>
                                <input type="text" name="child[name]" value="{{ old('child.name') }}"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">学年</label>
                                <input type="text" name="child[grade]" value="{{ old('child.grade') }}"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="例：小3 / 中1" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">拠点</label>
                                <input type="text" name="child[base]" value="{{ old('child.base') }}"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="例：〇〇児童クラブ" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">状態</label>
                                <select name="child[status]"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="active" @selected(old('child.status','active')==='active')>在籍</option>
                                    <option value="inactive" @selected(old('child.status')==='inactive')>退会</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 保護者情報（複数） --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold">保護者情報</h3>

                            <button type="button" id="addGuardianBtn"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                ＋ 保護者を追加
                            </button>
                        </div>

                        <div id="guardiansWrap" class="space-y-4">
                            {{-- 1人目（テンプレ） --}}
                        </div>

                        <template id="guardianTemplate">
                            <div class="rounded-lg border border-gray-200 p-4 bg-gray-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="font-semibold text-gray-800">保護者 <span class="gIndex"></span></div>
                                    <button type="button"
                                        class="removeGuardianBtn text-sm font-semibold text-red-600 hover:text-red-700">
                                        削除
                                    </button>
                                </div>

                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">氏名</label>
                                        <input type="text" class="gName mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">続柄（任意）</label>
                                        <input type="text" class="gRelation mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="例：父・母・祖父">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">LINE userId（任意）</label>
                                        <input type="text" class="gLine mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Uxxxxxxxx...">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">メール（任意）</label>
                                        <input type="email" class="gEmail mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">電話（任意）</label>
                                        <input type="text" class="gPhone mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="090...">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">優先連絡手段（任意）</label>
                                        <select class="gPreferred mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">未指定</option>
                                            <option value="line">LINE</option>
                                            <option value="email">メール</option>
                                            <option value="phone">電話</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <p class="mt-2 text-sm text-gray-600">
                            ※ 保護者は最大5人まで追加できます（運用で調整可）
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2 rounded-md bg-emerald-600 text-white font-semibold hover:bg-emerald-700">
                            登録する
                        </button>
                        <span class="text-sm text-gray-500">登録後もこの画面で続けて追加できます</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const wrap = document.getElementById('guardiansWrap');
            const tmpl = document.getElementById('guardianTemplate');
            const addBtn = document.getElementById('addGuardianBtn');

            function renumber() {
                const cards = wrap.querySelectorAll('[data-guardian-card]');
                cards.forEach((card, i) => {
                    card.querySelector('.gIndex').textContent = (i + 1);
                    const idx = i;

                    // name attributes を付与
                    card.querySelector('.gName').setAttribute('name', `guardians[${idx}][name]`);
                    card.querySelector('.gRelation').setAttribute('name', `guardians[${idx}][relation]`);
                    card.querySelector('.gLine').setAttribute('name', `guardians[${idx}][line_user_id]`);
                    card.querySelector('.gEmail').setAttribute('name', `guardians[${idx}][email]`);
                    card.querySelector('.gPhone').setAttribute('name', `guardians[${idx}][phone]`);
                    card.querySelector('.gPreferred').setAttribute('name', `guardians[${idx}][preferred_contact]`);
                });

                // 1人のとき削除ボタンを隠す
                const removeBtns = wrap.querySelectorAll('.removeGuardianBtn');
                removeBtns.forEach(btn => {
                    btn.style.display = (cards.length <= 1) ? 'none' : 'inline';
                });
            }

            function addGuardian(prefill = null) {
                const cards = wrap.querySelectorAll('[data-guardian-card]');
                if (cards.length >= 5) {
                    alert('保護者は最大5人までです');
                    return;
                }

                const node = tmpl.content.cloneNode(true);
                const card = node.querySelector('div');
                card.setAttribute('data-guardian-card', '1');

                // prefill（old値復元したい場合は後で拡張）
                if (prefill) {
                    card.querySelector('.gName').value = prefill.name ?? '';
                    card.querySelector('.gRelation').value = prefill.relation ?? '';
                    card.querySelector('.gLine').value = prefill.line_user_id ?? '';
                    card.querySelector('.gEmail').value = prefill.email ?? '';
                    card.querySelector('.gPhone').value = prefill.phone ?? '';
                    card.querySelector('.gPreferred').value = prefill.preferred_contact ?? '';
                }

                card.querySelector('.removeGuardianBtn').addEventListener('click', () => {
                    card.remove();
                    renumber();
                });

                wrap.appendChild(card);
                renumber();
            }

            addBtn.addEventListener('click', () => addGuardian());

            // 初期1人表示
            addGuardian();
        })();
    </script>
</x-app-layout>
