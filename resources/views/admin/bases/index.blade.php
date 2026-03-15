<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @php
                $canCreate = \App\Services\RolePermissionService::canUser(auth()->user(), 'bases_master', 'create');
                $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'bases_master', 'update');
                $canDelete = \App\Services\RolePermissionService::canUser(auth()->user(), 'bases_master', 'delete');
            @endphp
            <div class="flex items-center justify-between mb-4">
                {{-- タイトル（base.png + 拠点管理） --}}
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <img src="{{ asset('images/base.png') }}" alt="base" class="w-7 h-7 object-contain">
                    拠点管理
                </h1>

                {{-- 追加ボタン（add_base.png + 右に文字） --}}
                @if($canCreate)
                <a href="{{ route('admin.bases.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold
                          text-gray-900 bg-indigo-50 border border-indigo-200
                          shadow-sm transition-all duration-200
                          hover:bg-indigo-100 hover:-translate-y-0.5 hover:shadow
                          focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                    <img src="{{ asset('images/add_base.png') }}" alt="add" class="w-5 h-5 object-contain">
                    拠点を追加
                </a>
                @endif
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-3 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600 border-b">
                                <th class="py-2 pr-4">拠点名</th>
                                <th class="py-2 pr-4">状態</th>
                                <th class="py-2 pr-4 w-44">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bases as $base)
                                <tr class="border-b">
                                    <td class="py-3 pr-4 font-medium text-gray-800">
                                        {{ $base->name }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        @if($base->is_active)
                                            <span class="inline-flex px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs">有効</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 rounded-full bg-red-600 text-white text-xs">無効</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2">
                                            @if($canUpdate)
                                                <a href="{{ route('admin.bases.edit', $base) }}"
                                                   class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold
                                                          text-amber-900 bg-amber-50 border border-amber-200
                                                          shadow-sm transition hover:bg-amber-100
                                                          focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2">
                                                    編集
                                                </a>
                                            @endif

                                            @if($canDelete)
                                                <form method="POST" action="{{ route('admin.bases.destroy', $base) }}"
                                                      onsubmit="return confirm('この拠点を削除します。よろしいですか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-semibold
                                                               text-rose-900 bg-rose-50 border border-rose-200
                                                               shadow-sm transition hover:bg-rose-100
                                                               focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2">
                                                        削除
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-gray-500">
                                        拠点がまだ登録されていません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $bases->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
