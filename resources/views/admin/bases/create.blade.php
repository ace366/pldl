<x-app-layout>
    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-800">拠点を追加</h1>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.bases.store') }}">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">拠点名</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <input id="is_active" type="checkbox" name="is_active" value="1" checked
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm text-gray-700">有効にする</label>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <a href="{{ route('admin.bases.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 underline">
                            戻る
                        </a>

                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-sm hover:bg-indigo-700">
                            登録する
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
