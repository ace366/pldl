<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">学校マスタ</h2>
            <a href="{{ route('admin.schools.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                ＋ 学校を追加
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3">学校名</th>
                            <th class="text-left text-sm font-semibold text-gray-700 px-4 py-3">状態</th>
                            <th class="text-right text-sm font-semibold text-gray-700 px-4 py-3">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($schools as $school)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $school->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($school->is_active)
                                        <span class="inline-flex px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-semibold">有効</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-600 text-xs font-semibold">無効</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('admin.schools.edit', $school) }}"
                                       class="text-indigo-600 font-semibold hover:underline">編集</a>

                                    <form action="{{ route('admin.schools.destroy', $school) }}" method="POST" class="inline"
                                          onsubmit="return confirm('削除しますか？（ユーザーが紐づいている場合は学校が未設定になります）')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-red-600 font-semibold hover:underline">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $schools->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
