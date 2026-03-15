<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @php
                $canCreate = \App\Services\RolePermissionService::canUser(auth()->user(), 'guardians_index', 'create');
            @endphp
            <div class="flex items-center justify-between mb-4">
                {{-- タイトル（guardian.png + 保護者管理） --}}
                <h1 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <img src="{{ asset('images/guardian.png') }}" alt="guardian" class="w-7 h-7 object-contain">
                    保護者管理
                </h1>

                {{-- 追加ボタン（add_guardian.png + 右に文字） --}}
                @if($canCreate)
                <a href="{{ route('admin.guardians.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold
                          text-gray-900 bg-indigo-50 border border-indigo-200
                          shadow-sm transition-all duration-200
                          hover:bg-indigo-100 hover:-translate-y-0.5 hover:shadow
                          focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2">
                    <img src="{{ asset('images/add_guardian.png') }}" alt="add guardian" class="w-5 h-5 object-contain">
                    保護者を追加
                </a>
                @endif
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET"
                    action="{{ route('admin.guardians.index') }}"
                    class="flex items-end gap-3">

                    <div class="flex-1">
                        <label class="block text-xs text-gray-600 mb-1">
                            検索（氏名 / ふりがな / メール / 電話 / LINE）
                        </label>
                        <input type="text"
                            name="q"
                            value="{{ $q }}"
                            class="block w-full rounded-md border-gray-300
                                    focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="例：山田 / 090 / line_user_id">
                    </div>

                    <button type="submit"
                            class="h-10 px-4 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-900">
                        🔍
                    </button>

                    <a href="{{ route('admin.guardians.index') }}"
                    class="h-10 flex items-center px-3 text-sm text-gray-600 hover:text-gray-900 underline">
                        リセット
                    </a>
                </form>
            </div>


            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 border-collapse text-center">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-600">
                                <th class="py-2 px-3 border">氏名</th>
                                <th class="py-2 px-3 border">ふりがな</th>
                                <th class="py-2 px-3 border">メール</th>
                                <th class="py-2 px-3 border">電話</th>
                                <th class="py-2 px-3 border">LINE</th>
                                <th class="py-2 px-3 border">優先</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($guardians as $g)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 border font-medium text-gray-800">
                                        {{ $g->last_name }} {{ $g->first_name }}
                                    </td>
                                    <td class="py-3 px-3 border text-gray-700">
                                        {{ trim(($g->last_name_kana ?? '').' '.($g->first_name_kana ?? '')) }}
                                    </td>
                                    <td class="py-3 px-3 border text-gray-700">
                                        {{ $g->email ?? '—' }}
                                    </td>
                                    <td class="py-3 px-3 border text-gray-700">
                                        {{ $g->phone ?? '—' }}
                                    </td>
                                    <td class="py-3 px-3 border text-gray-700">
                                        {{ $g->line_user_id ?? '—' }}
                                    </td>
                                    <td class="py-3 px-3 border">
                                        @php
                                            $label = match($g->preferred_contact) {
                                                'line' => 'LINE',
                                                'email' => 'メール',
                                                'phone' => '電話',
                                                default => '—',
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                            {{ $label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 border text-gray-500">
                                        保護者がまだ登録されていません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>


                    <div class="mt-4">
                        {{ $guardians->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
