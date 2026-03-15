<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">学校を追加</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.schools.store') }}">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">学校名</label>
                        <input name="name" value="{{ old('name') }}" required
                               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                        有効
                    </label>

                    <div class="mt-6 flex gap-3">
                        <button class="px-5 py-2 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                            登録
                        </button>
                        <a href="{{ route('admin.schools.index') }}" class="px-5 py-2 rounded-md border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50">
                            戻る
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
