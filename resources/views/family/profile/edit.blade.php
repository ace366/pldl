<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-4 sm:p-6">
                <h1 class="text-lg font-semibold text-gray-900">登録内容の変更</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $child->full_name ?? ($child->last_name.' '.$child->first_name) }} さんに紐づく保護者情報を更新できます。
                </p>

                @if (session('success'))
                    <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('line_link_error'))
                    <div class="mt-4 rounded-md bg-rose-50 p-3 text-sm text-rose-700">
                        {{ session('line_link_error') }}
                    </div>
                @endif

                @php
                    $addGuardianFieldNames = [
                        'new_last_name',
                        'new_first_name',
                        'new_last_name_kana',
                        'new_first_name_kana',
                        'new_email',
                        'new_phone',
                        'new_emergency_phone_label',
                        'new_emergency_phone',
                        'new_preferred_contact',
                        'new_relationship',
                    ];
                    $hasAddGuardianErrors = collect($addGuardianFieldNames)->contains(fn ($field) => $errors->has($field));
                    $showAddGuardianForm = request()->boolean('add_guardian') || $hasAddGuardianErrors || old('new_last_name') || old('new_first_name');
                @endphp

                @if($targetGuardian)
                    <div class="mt-6 rounded-xl border border-sky-200 bg-sky-50 p-4 sm:p-5">
                        <h2 class="text-base font-semibold text-sky-900">LINE連携</h2>
                        <p class="mt-1 text-sm text-sky-800">
                            現在の保護者（{{ $targetGuardian->full_name ?: ('保護者 #'.$targetGuardian->id) }}）をLINEに連携します。
                        </p>
                        <div class="mt-3 text-sm text-gray-700">
                            連携状態：
                            @if(!empty($targetGuardian->line_user_id))
                                <span class="font-semibold text-emerald-700">連携済み</span>
                                <span class="ml-2 text-xs text-gray-500">ID: {{ \Illuminate\Support\Str::limit($targetGuardian->line_user_id, 22, '...') }}</span>
                            @else
                                <span class="font-semibold text-amber-700">未連携</span>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('family.line.link', ['guardian_id' => (int)$targetGuardian->id]) }}"
                               class="inline-flex items-center rounded-xl bg-sky-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-sky-700">
                                LINEログインで連携する
                            </a>

                            <form method="POST" action="{{ route('family.line.link_token.create') }}">
                                @csrf
                                <input type="hidden" name="guardian_id" value="{{ (int)$targetGuardian->id }}">
                                <button type="submit"
                                        class="inline-flex items-center rounded-xl border border-sky-300 bg-white px-6 py-3 text-base font-semibold text-sky-800 hover:bg-sky-100">
                                    LINE連携コードを発行する
                                </button>
                            </form>
                        </div>

                        @if(session('line_link_code'))
                            <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
                                <div class="text-sm text-amber-900">5分以内に下のコードをLINEへ送ってください（有効期限 {{ session('line_link_code_expires_at') }}）</div>
                                <div class="mt-2 text-3xl font-extrabold tracking-[0.25em] text-amber-900">
                                    {{ e(session('line_link_code')) }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if(($guardians ?? collect())->count() > 1)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($guardians as $g)
                            <a href="{{ route('family.profile.edit', ['guardian_id' => $g->id]) }}"
                               class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ (int)$g->id === (int)$targetGuardian->id ? 'bg-emerald-100 border-emerald-300 text-emerald-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                {{ $g->full_name ?: ('保護者 #'.$g->id) }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($targetGuardian)
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50/40 p-4">
                        <h2 class="text-sm font-semibold text-emerald-900">メッセージアイコン設定</h2>
                        <p class="mt-1 text-xs text-emerald-800">
                            この端末で選択中の保護者（{{ $targetGuardian->full_name ?: ('保護者 #'.$targetGuardian->id) }}）のアイコンを設定できます。
                        </p>

                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $avatarPreviewUrl ?? route('family.profile.avatar.show', ['child_id' => (int)$child->id]) }}"
                                 alt="保護者アイコン"
                                 class="h-14 w-14 rounded-full border border-emerald-200 bg-white object-cover shadow-sm">
                            <div class="text-xs text-gray-600">
                                jpg / jpeg / png / webp / gif（5MB以下）
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('family.profile.avatar.update') }}"
                              enctype="multipart/form-data"
                              class="mt-3 space-y-3">
                            @csrf
                            <input type="hidden" name="guardian_id" value="{{ (int)$targetGuardian->id }}">

                            <div>
                                <input type="file"
                                       name="avatar"
                                       accept="image/jpeg,image/png,image/webp,image/gif"
                                       class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700">
                                @error('avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    アイコンを更新する
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('family.profile.avatar.update') }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="guardian_id" value="{{ (int)$targetGuardian->id }}">
                            <input type="hidden" name="remove_avatar" value="1">
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                アイコンを削除する
                            </button>
                        </form>
                    </div>
                @endif

                @if($targetGuardian)
                    <form method="POST" action="{{ route('family.profile.update') }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="guardian_id" value="{{ (int)$targetGuardian->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $targetGuardian->last_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $targetGuardian->first_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                                <input type="text" name="last_name_kana" value="{{ old('last_name_kana', $targetGuardian->last_name_kana) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                @error('last_name_kana')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                                <input type="text" name="first_name_kana" value="{{ old('first_name_kana', $targetGuardian->first_name_kana) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                @error('first_name_kana')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">メール</label>
                            <input type="email" name="email" value="{{ old('email', $targetGuardian->email) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">電話</label>
                            <input type="hidden" id="guardian_phone" name="phone" value="{{ old('phone', $targetGuardian->phone) }}">
                            <input type="tel" id="guardian_phone_display"
                                   inputmode="numeric" autocomplete="tel"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="例：090-1234-5678">
                            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">緊急連絡先（どこの番号か）</label>
                            <input type="text" name="emergency_phone_label"
                                   value="{{ old('emergency_phone_label', $targetGuardian->emergency_phone_label) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="例：父勤務先 / 祖母宅">
                            @error('emergency_phone_label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">緊急連絡先（電話）</label>
                            <input type="hidden" id="guardian_emergency_phone" name="emergency_phone"
                                   value="{{ old('emergency_phone', $targetGuardian->emergency_phone) }}">
                            <input type="tel" id="guardian_emergency_phone_display"
                                   inputmode="numeric" autocomplete="tel"
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="例：0277-12-3456">
                            @error('emergency_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">優先連絡手段</label>
                            <select name="preferred_contact"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">未設定</option>
                                <option value="email" @selected(old('preferred_contact', $targetGuardian->preferred_contact) === 'email')>メール</option>
                                <option value="phone" @selected(old('preferred_contact', $targetGuardian->preferred_contact) === 'phone')>電話</option>
                                <option value="line" @selected(old('preferred_contact', $targetGuardian->preferred_contact) === 'line')>LINE</option>
                            </select>
                            @error('preferred_contact')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">備考（任意）</label>
                            <textarea name="note" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500"
                                      placeholder="例：アレルギー等">{{ old('note', $child->note) }}</textarea>
                            @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <a href="{{ route('family.home') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">戻る</a>
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                更新する
                            </button>
                        </div>
                    </form>
                @else
                    <div class="mt-6 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                        紐づいている保護者情報がありません。下の「保護者を追加」から登録してください。
                    </div>
                @endif

                <div class="mt-8 border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-gray-900">保護者を追加</h2>
                        @if($showAddGuardianForm)
                            <a href="{{ route('family.profile.edit', ['guardian_id' => (int)($targetGuardian->id ?? 0)]) }}"
                               class="text-sm text-gray-600 hover:text-gray-900 underline">
                                キャンセル
                            </a>
                        @else
                            <a href="{{ route('family.profile.edit', ['guardian_id' => (int)($targetGuardian->id ?? 0), 'add_guardian' => 1]) }}"
                               class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                + 保護者を増やす
                            </a>
                        @endif
                    </div>

                    @if($showAddGuardianForm)
                        <form method="POST" action="{{ route('family.profile.guardians.store') }}" class="mt-4 space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">姓（漢字）</label>
                                    <input type="text" name="new_last_name" value="{{ old('new_last_name') }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('new_last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">名（漢字）</label>
                                    <input type="text" name="new_first_name" value="{{ old('new_first_name') }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('new_first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">せい（ふりがな）</label>
                                    <input type="text" name="new_last_name_kana" value="{{ old('new_last_name_kana') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('new_last_name_kana')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">めい（ふりがな）</label>
                                    <input type="text" name="new_first_name_kana" value="{{ old('new_first_name_kana') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('new_first_name_kana')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">続柄（任意）</label>
                                <input type="text" name="new_relationship" value="{{ old('new_relationship') }}"
                                       placeholder="例：母 / 父 / 祖母"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('new_relationship')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">メール</label>
                                <input type="email" name="new_email" value="{{ old('new_email') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @error('new_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">電話</label>
                                <input type="hidden" id="new_guardian_phone" name="new_phone" value="{{ old('new_phone') }}">
                                <input type="tel" id="new_guardian_phone_display"
                                       inputmode="numeric" autocomplete="tel"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="例：090-1234-5678">
                                @error('new_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">緊急連絡先（どこの番号か）</label>
                                <input type="text" name="new_emergency_phone_label" value="{{ old('new_emergency_phone_label') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="例：父勤務先 / 祖母宅">
                                @error('new_emergency_phone_label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">緊急連絡先（電話）</label>
                                <input type="hidden" id="new_guardian_emergency_phone" name="new_emergency_phone"
                                       value="{{ old('new_emergency_phone') }}">
                                <input type="tel" id="new_guardian_emergency_phone_display"
                                       inputmode="numeric" autocomplete="tel"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="例：0277-12-3456">
                                @error('new_emergency_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">優先連絡手段</label>
                                <select name="new_preferred_contact"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">未設定</option>
                                    <option value="email" @selected(old('new_preferred_contact') === 'email')>メール</option>
                                    <option value="phone" @selected(old('new_preferred_contact') === 'phone')>電話</option>
                                    <option value="line" @selected(old('new_preferred_contact') === 'line')>LINE</option>
                                </select>
                                @error('new_preferred_contact')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    保護者を追加する
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const formatJapanPhoneDisplay = (digits) => {
                if (!digits) return '';

                if (digits.startsWith('0120')) {
                    if (digits.length <= 4) return digits;
                    if (digits.length <= 7) return digits.slice(0, 4) + '-' + digits.slice(4);
                    return digits.slice(0, 4) + '-' + digits.slice(4, 7) + '-' + digits.slice(7, 10);
                }

                if (digits.startsWith('03') || digits.startsWith('06')) {
                    if (digits.length <= 2) return digits;
                    if (digits.length <= 6) return digits.slice(0, 2) + '-' + digits.slice(2);
                    return digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6, 10);
                }

                if (digits.startsWith('070') || digits.startsWith('080') || digits.startsWith('090') || digits.startsWith('050')) {
                    if (digits.length <= 3) return digits;
                    if (digits.length <= 7) return digits.slice(0, 3) + '-' + digits.slice(3);
                    return digits.slice(0, 3) + '-' + digits.slice(3, 7) + '-' + digits.slice(7, 11);
                }

                if (digits.length <= 3) return digits;
                if (digits.length <= 6) return digits.slice(0, 3) + '-' + digits.slice(3);
                return digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6, 10);
            };

            const wirePhone = (hiddenId, displayId) => {
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
            };

            wirePhone('guardian_phone', 'guardian_phone_display');
            wirePhone('guardian_emergency_phone', 'guardian_emergency_phone_display');
            wirePhone('new_guardian_phone', 'new_guardian_phone_display');
            wirePhone('new_guardian_emergency_phone', 'new_guardian_emergency_phone_display');
        })();
    </script>
</x-app-layout>
