<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @php
                $canCreate = \App\Services\RolePermissionService::canUser(auth()->user(), 'schools_master', 'create');
                $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'schools_master', 'update');
                $canDelete = \App\Services\RolePermissionService::canUser(auth()->user(), 'schools_master', 'delete');
            @endphp
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-indigo-50 border border-indigo-200 shadow-sm">
                        <img
                            src="data:image/png;base64,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"
                            alt="学校アイコン"
                            class="w-10 h-10 object-contain"
                        >
                    </span>
                    <span>学校マスタ</span>
                </h1>


                    @if($canCreate)
                    <a href="{{ route('admin.schools.create') }}"
                    class="group inline-flex flex-col items-center gap-1">
                        <span
                            class="inline-flex items-center justify-center w-12 h-12 rounded-full
                                bg-indigo-50 border border-indigo-200 shadow-sm
                                transition-all duration-200
                                group-hover:bg-indigo-100 group-hover:-translate-y-0.5 group-hover:shadow
                                focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                            <img
                                src="data:image/png;base64,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"
                                alt="school-emoji"
                                class="w-8 h-8 object-contain"
                            >
                        </span>

                        <span class="text-sm font-semibold text-gray-900">
                           + 学校を追加
                        </span>
                    </a>
                    @endif

            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- かんたん追加（この画面で追加できる） --}}
            @if($canCreate)
                <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                    <form method="POST" action="{{ route('admin.schools.store') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600">学校名</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="例：みどり市立笠懸小学校">
                        </div>
                        <div class="flex items-end gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                                有効
                            </label>
                            <button type="submit"
                                    class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-900">
                                追加
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-gray-600 border-b">
                            <th class="py-2 pr-4">ID</th>
                            <th class="py-2 pr-4">学校名</th>
                            <th class="py-2 pr-4">状態</th>
                            <th class="py-2 pr-4 w-40">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($schools as $s)
                            <tr class="border-b">
                                <td class="py-3 pr-4 text-gray-700">{{ $s->id }}</td>
                                <td class="py-3 pr-4 font-medium text-gray-900">{{ $s->name }}</td>
                                <td class="py-3 pr-4">
                                    @if((int)$s->is_active === 1)
                                        <span class="inline-flex px-2 py-1 rounded bg-green-100 text-green-800 text-xs">有効</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-full bg-red-600 text-white text-xs">無効</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 flex items-center gap-2">
                                    @if($canUpdate)
                                        <a href="{{ route('admin.schools.edit', $s) }}"
                                           class="inline-flex items-center px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs">
                                            編集
                                        </a>
                                    @endif

                                    @if($canDelete)
                                        <form method="POST" action="{{ route('admin.schools.destroy', $s) }}"
                                              onsubmit="return confirm('削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1 rounded bg-red-100 hover:bg-red-200 text-red-800 text-xs">
                                                削除
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500">学校がまだ登録されていません。</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $schools->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
