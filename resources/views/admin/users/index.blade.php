<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-4">
                <h1 class="text-xl font-semibold text-gray-800">ユーザー管理（admin専用）</h1>
                <a href="{{ route('admin.payroll.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                    従業員給与一覧へ
                </a>
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

            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">検索</label>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="氏名 / ふりがな / メール"
                               class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 h-10 rounded-md bg-gray-800 text-white text-sm font-semibold hover:bg-gray-900">
                        検索
                    </button>

                    <a href="{{ route('admin.users.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 underline">
                        リセット
                    </a>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-4 sm:px-6 py-5 overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200 border-collapse">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-600 text-center">
                                <th class="py-2 px-3 border">ID</th>
                                <th class="py-2 px-3 border">氏名</th>
                                <th class="py-2 px-3 border">メール</th>
                                <th class="py-2 px-3 border">電話</th>
                                <th class="py-2 px-3 border">role</th>
                                <th class="py-2 px-3 border">時給(円)</th>
                                <th class="py-2 px-3 border w-56">操作</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($users as $u)
                                @php
                                    $role = $u->role ?? 'user';
                                    $isAdmin = (string)(auth()->user()->role ?? '') === 'admin';
                                    $canUpdate = \App\Services\RolePermissionService::canUser(auth()->user(), 'admin_users', 'update');
                                    $isSelf = auth()->id() === $u->id;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 border text-center text-gray-700">{{ $u->id }}</td>

                                    <td class="py-3 px-3 border text-gray-900 font-semibold">
                                        {{ $u->name ?? trim(($u->last_name ?? '').' '.($u->first_name ?? '')) }}
                                        @php
                                            $kana = trim(($u->last_name_kana ?? '').' '.($u->first_name_kana ?? ''));
                                        @endphp
                                        @if($kana !== '')
                                            <div class="text-xs text-gray-500 font-normal">{{ $kana }}</div>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border text-gray-700">{{ $u->email }}</td>

                                    <td class="py-3 px-3 border text-gray-700 text-center">
                                        {{ $u->phone ?? '—' }}
                                    </td>

                                    <td class="py-3 px-3 border text-center">
                                        @if($role === 'admin')
                                            <span class="inline-flex px-2 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold">
                                                admin
                                            </span>
                                        @elseif($role === 'staff')
                                            <span class="inline-flex px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                                staff
                                            </span>
                                        @elseif($role === 'teacher')
                                            <span class="inline-flex px-2 py-1 rounded bg-amber-100 text-amber-700 text-xs font-semibold">
                                                teacher
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs font-semibold">
                                                user
                                            </span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-3 border text-right font-semibold text-gray-800">
                                        {{ is_null($u->hourly_wage) ? '—' : number_format((int)$u->hourly_wage).'円' }}
                                    </td>

                                    <td class="py-3 px-3 border">
                                        @if($isAdmin && $canUpdate && !$isSelf)
                                            <form method="POST" action="{{ route('admin.users.updateRole', $u) }}"
                                                  class="flex items-center justify-center gap-2">
                                                @csrf
                                                @method('PATCH')

                                                <select name="role"
                                                        class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                    @foreach($roles as $r)
                                                        <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                                                    @endforeach
                                                </select>

                                                <input type="number"
                                                       name="hourly_wage"
                                                       min="0"
                                                       step="1"
                                                       value="{{ old('hourly_wage', $u->hourly_wage) }}"
                                                       placeholder="時給"
                                                       class="w-24 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right">

                                                <button type="submit"
                                                        onclick="return confirm('このユーザーの権限・時給を変更します。よろしいですか？')"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-md bg-gray-800 text-white text-xs font-semibold hover:bg-gray-900">
                                                    更新
                                                </button>
                                            </form>
                                        @else
                                            <div class="text-center text-xs text-gray-400 py-2">
                                                —
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 border text-center text-gray-500">
                                        ユーザーが見つかりません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
