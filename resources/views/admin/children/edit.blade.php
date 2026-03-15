<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            {{-- タイトル --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        <img src="{{ asset('images/user100.png') }}" alt="user" class="w-7 h-7 object-contain">
                        児童を編集
                    </h1>
                    <div class="text-sm text-gray-600">
                        {{ $child->last_name }} {{ $child->first_name }}（{{ $child->grade }}年）
                    </div>
                </div>

                <a href="{{ route('admin.children.index') }}"
                   class="text-sm text-gray-600 hover:text-gray-900 underline">
                    一覧へ戻る
                </a>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    <div class="font-semibold mb-1">入力エラーがあります</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ✅ 1) 検索エリアは POSTフォームの外（入れ子禁止回避） --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <div class="lg:col-span-2"></div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h2 id="guardian-link" class="text-base font-semibold text-gray-800 mb-4">
                        保護者の紐づけ（検索）
                    </h2>

                    <form method="GET"
                          action="{{ route('admin.children.edit', $child) }}"
                          class="flex gap-2"
                          onsubmit="return submitGuardianSearch(this);">
                        <input type="text"
                               name="qg"
                               value="{{ $qg }}"
                               class="flex-1 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="氏名/電話/メール/LINE">
                        <button class="px-3 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-900" type="submit">
                            検索
                        </button>
                    </form>

                    <div class="mt-2">
                        <a href="{{ route('admin.children.edit', $child) }}"
                           class="text-xs text-gray-600 underline hover:text-gray-900"
                           onclick="return clearGuardianSearch();">
                            検索条件をクリア
                        </a>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        見つからない場合は、先に「保護者管理」で登録してください。
                    </p>

                    {{-- ✅検索結果（押すと下のPOSTフォームの「紐づけ済み」に追加される） --}}
                    @if(($qg ?? '') !== '')
                        <div class="mt-4">
                            <div class="text-xs font-semibold text-gray-600 mb-2">検索結果（最大20件）</div>
                            <div class="space-y-2">
                                @forelse($guardianResults as $g)
                                    <button type="button"
                                            class="w-full text-left px-3 py-2 rounded border hover:bg-gray-50"
                                            onclick="addGuardian({{ $g->id }}, @js($g->full_name), @js($g->email), @js($g->phone))">
                                        <div class="font-medium text-gray-800">{{ $g->full_name }}</div>
                                        <div class="text-xs text-gray-600">
                                            {{ $g->email ?? '—' }} / {{ $g->phone ?? '—' }}
                                        </div>
                                    </button>
                                @empty
                                    <div class="text-sm text-gray-500">該当する保護者が見つかりません。</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ✅ 2) ここから児童更新POSTフォーム（検索フォームは絶対に入れない） --}}
            <form method="POST"
                  action="{{ route('admin.children.update', $child) }}"
                  class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @csrf
                @method('PUT')

                {{-- 左：児童情報 --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-2">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">児童情報</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $child->last_name) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $child->first_name) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                            <input type="text" name="last_name_kana" value="{{ old('last_name_kana', $child->last_name_kana) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="ひらがな">
                            @error('last_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                            <input type="text" name="first_name_kana" value="{{ old('first_name_kana', $child->first_name_kana) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="ひらがな">
                            @error('first_name_kana')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                        {{-- 学年 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">学年</label>
                            <select name="grade" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @for($i=1;$i<=6;$i++)
                                    <option value="{{ $i }}" @selected((string)old('grade', $child->grade)===(string)$i)>{{ $i }}年</option>
                                @endfor
                            </select>
                            @error('grade')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- 学校（★追加） --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">学校</label>
                            <select name="school_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" @selected((string)old('school_id', $child->school_id)===(string)$s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- 拠点 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">拠点</label>
                            <select name="base_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">未設定</option>
                                @foreach($bases as $b)
                                    <option value="{{ $b->id }}" @selected((string)old('base_id', $child->base_id)===(string)$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('base_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>


                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">状態</label>
                        <select name="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="enrolled" @selected(old('status', $child->status)==='enrolled')>在籍</option>
                            <option value="withdrawn" @selected(old('status', $child->status)==='withdrawn')>退会</option>
                        </select>
                        @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">備考</label>
                        <textarea name="note" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="例：喘息があるため運動後は休憩が必要">{{ old('note', $child->note) }}</textarea>
                        @error('note')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- 保存ボタン（save.png + 下に黒文字） --}}
                    <div class="mt-6 flex items-center justify-end">
                        <button type="submit" class="group inline-flex flex-col items-center gap-1">
                            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                         bg-slate-50 border border-slate-200 shadow-sm
                                         transition-all duration-200
                                         group-hover:bg-slate-100 group-hover:-translate-y-0.5 group-hover:shadow
                                         focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                                <img src="{{ asset('images/save.png') }}" alt="保存する" class="w-7 h-7 object-contain">
                            </span>
                            <span class="text-sm font-semibold text-gray-900">保存する</span>
                        </button>
                    </div>
                </div>

                {{-- 右：保護者紐づけ（紐づけ済み編集＆hidden追加はここでPOST送信される） --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">保護者の紐づけ（紐づけ済み）</h2>

                    <div class="text-xs text-gray-500 mb-3">
                        ※ 上の検索結果から追加したあと、最後に「保存する」を押してください。
                    </div>

                    <div id="selectedGuardians" class="space-y-2">
                        @foreach($child->guardians as $g)
                            <div class="p-3 rounded border" data-guardian-id="{{ $g->id }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-medium text-gray-800">{{ $g->full_name }}</div>
                                        <div class="text-xs text-gray-600">{{ $g->email ?? '—' }} / {{ $g->phone ?? '—' }}</div>
                                    </div>
                                    <button type="button"
                                            class="text-xs px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200"
                                            onclick="removeGuardian({{ $g->id }})">
                                        外す
                                    </button>
                                </div>

                                <div class="mt-2">
                                    <label class="block text-xs text-gray-600">続柄（任意）</label>
                                    <input type="text"
                                           name="relationships[{{ $g->id }}]"
                                           value="{{ old('relationships.'.$g->id, $g->pivot->relationship) }}"
                                           class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="例：母 / 父 / 祖母">
                                </div>

                                <input type="hidden" name="guardian_ids[]" value="{{ $g->id }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        // ✅検索ボタンで「同じ位置を保つ」：GET送信時に #guardian-link を付けて遷移
        function submitGuardianSearch(formEl) {
            const url = new URL(formEl.action, window.location.origin);
            const fd = new FormData(formEl);
            const qg = (fd.get('qg') || '').toString().trim();
            if (qg !== '') url.searchParams.set('qg', qg);
            url.hash = 'guardian-link';
            window.location.href = url.toString();
            return false;
        }

        // ✅検索条件クリアも同じ位置
        function clearGuardianSearch() {
            const url = new URL(@js(route('admin.children.edit', $child)), window.location.origin);
            url.hash = 'guardian-link';
            window.location.href = url.toString();
            return false;
        }

        // すでに選択済みの guardian_id をSetで管理
        const selected = new Set(
            Array.from(document.querySelectorAll('#selectedGuardians [data-guardian-id]'))
                .map(el => Number(el.getAttribute('data-guardian-id')))
        );

        function addGuardian(id, name, email, phone) {
            id = Number(id);
            if (selected.has(id)) {
                alert('すでに追加済みです。');
                return;
            }
            selected.add(id);

            const wrap = document.createElement('div');
            wrap.className = 'p-3 rounded border';
            wrap.setAttribute('data-guardian-id', String(id));

            wrap.innerHTML = `
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-medium text-gray-800">${escapeHtml(name)}</div>
                        <div class="text-xs text-gray-600">${escapeHtml(email ?? '—')} / ${escapeHtml(phone ?? '—')}</div>
                    </div>
                    <button type="button"
                            class="text-xs px-2 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200"
                            onclick="removeGuardian(${id})">
                        外す
                    </button>
                </div>

                <div class="mt-2">
                    <label class="block text-xs text-gray-600">続柄（任意）</label>
                    <input type="text"
                           name="relationships[${id}]"
                           value=""
                           class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="例：母 / 父 / 祖母">
                </div>

                <input type="hidden" name="guardian_ids[]" value="${id}">
            `;

            document.getElementById('selectedGuardians').prepend(wrap);
        }

        function removeGuardian(id) {
            id = Number(id);
            const el = document.querySelector(`#selectedGuardians [data-guardian-id="${id}"]`);
            if (el) el.remove();
            selected.delete(id);
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
</x-app-layout>
