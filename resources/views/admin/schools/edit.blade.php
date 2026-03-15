<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-800">学校を編集</h1>
                <div class="text-sm text-gray-600">{{ $school->name }}</div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.schools.update', $school) }}">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">学校名</label>
                        <input type="text" name="name" value="{{ old('name', $school->name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1"
                                   @checked((int)old('is_active', $school->is_active) === 1)
                                   class="rounded border-gray-300">
                            有効
                        </label>
                        @error('is_active')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('admin.schools.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
                            戻る
                        </a>

                        {{-- 送信ボタン：アイコン＋下に黒文字 --}}
                        <button type="submit"
                                class="group inline-flex flex-col items-center gap-1">
                            <span
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                       bg-indigo-50 border border-indigo-200 shadow-sm
                                       transition-all duration-200
                                       group-hover:bg-indigo-100 group-hover:-translate-y-0.5 group-hover:shadow
                                       focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                                <img
                                    src="{{ asset('images/school96.png') }}"
                                    alt="学校（更新）"
                                    class="w-10 h-10 object-contain">
                            </span>

                            <span class="text-sm font-semibold text-gray-900">
                                更新する
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
