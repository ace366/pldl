<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-800">児童を追加</h1>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.children.store') }}">
                    @csrf

                    {{-- 氏名（漢字） --}}
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

                    {{-- ふりがな --}}
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

                    {{-- 学年 / 学校 / 拠点 --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">学年</label>
                            <select name="grade" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">選択</option>
                                @for($i=1;$i<=6;$i++)
                                    <option value="{{ $i }}" @selected((string)old('grade')===(string)$i)>{{ $i }}年</option>
                                @endfor
                            </select>
                            @error('grade')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">学校</label>
                            <select name="school_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">選択</option>
                                @foreach($schools as $s)
                                    <option value="{{ $s->id }}" @selected((string)old('school_id')===(string)$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('school_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">拠点</label>
                            <select name="base_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">未設定</option>
                                @foreach($bases as $b)
                                    <option value="{{ $b->id }}" @selected((string)old('base_id')===(string)$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            @error('base_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- 状態 --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">状態</label>
                        <select name="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="enrolled" @selected(old('status','enrolled')==='enrolled')>在籍</option>
                            <option value="withdrawn" @selected(old('status')==='withdrawn')>退会</option>
                        </select>
                        @error('status')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- 備考 --}}
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">備考</label>
                        <textarea name="note" rows="4"
                                  class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="例：喘息があるため運動後は休憩が必要">{{ old('note') }}</textarea>
                        @error('note')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- 戻る / 登録 --}}
                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('admin.children.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
                            戻る
                        </a>

                        <button type="submit" class="group inline-flex flex-col items-center gap-1">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                    bg-indigo-50 border border-indigo-200 shadow-sm
                                    transition-all duration-200
                                    group-hover:bg-indigo-100 group-hover:-translate-y-0.5 group-hover:shadow
                                    focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                                <img src="{{ asset('images/user100.png') }}" alt="登録" class="w-7 h-7 object-contain">
                            </span>
                            <span class="text-sm font-semibold text-gray-900">登録する</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>
