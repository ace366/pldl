<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">権限設定（ロール別）</h1>
                        <div class="text-sm text-gray-500 mt-1">表示/閲覧/編集/追加/削除をロールごとに設定できます。</div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-md bg-green-50 p-3 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4 overflow-x-auto">
                <form method="POST" action="{{ route('admin.permissions.update', [], false) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50/40 p-4">
                        <div class="text-sm font-semibold text-indigo-900">新規登録ロック設定</div>
                        <p class="mt-1 text-xs text-indigo-800">
                            `/login` の「新規登録」に進む前に要求する事前共有パスワードです。保存後すぐ反映されます。
                        </p>

                        <div class="mt-3 max-w-xl">
                            <label for="registration_gate_password" class="block text-xs font-medium text-indigo-900">ロック用パスワード</label>
                            <div class="relative mt-1">
                                <input id="registration_gate_password"
                                       name="registration_gate_password"
                                       type="password"
                                       value="{{ old('registration_gate_password', $registrationGatePassword ?? '') }}"
                                       class="w-full rounded-md border-indigo-200 pr-12 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                                <button type="button"
                                        id="toggle-registration-gate-password"
                                        class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-indigo-500 hover:text-indigo-700"
                                        aria-label="パスワードを表示">
                                    <svg id="registration-eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg id="registration-eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.092A9.944 9.944 0 0112 5c4.478 0 8.269 2.943 9.543 7a10.023 10.023 0 01-4.132 5.411M6.228 6.228A9.956 9.956 0 002.457 12a10.025 10.025 0 003.305 4.627" />
                                    </svg>
                                </button>
                            </div>
                            @error('registration_gate_password')
                                <p class="mt-2 text-xs text-rose-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <table class="min-w-full text-xs border border-gray-200 border-collapse">
                        <thead class="bg-gray-50">
                            <tr class="text-gray-600 text-center">
                                <th class="py-2 px-3 border text-left">機能</th>
                                @foreach($roles as $role)
                                    <th class="py-2 px-3 border" colspan="4">{{ $role }}</th>
                                @endforeach
                            </tr>
                            <tr class="text-gray-500 text-center">
                                <th class="py-2 px-3 border"></th>
                                @foreach($roles as $role)
                                    <th class="py-2 px-2 border">表示</th>
                                    <th class="py-2 px-2 border">追加</th>
                                    <th class="py-2 px-2 border">編集</th>
                                    <th class="py-2 px-2 border">削除</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($features as $featureKey => $featureLabel)
                                @php
                                    $featureLinks = [
                                        'my_qr' => \Illuminate\Support\Facades\Route::has('myqr.show') ? route('myqr.show') : null,
                                        'today_attendance' => \Illuminate\Support\Facades\Route::has('staff.attendance.today') ? route('staff.attendance.today') : null,
                                        'attendance_qr' => \Illuminate\Support\Facades\Route::has('staff.attendance.qr') ? route('staff.attendance.qr') : null,
                                        'child_qr_scan' => \Illuminate\Support\Facades\Route::has('admin.attendance.scan') ? route('admin.attendance.scan') : null,
                                        'shift_day' => \Illuminate\Support\Facades\Route::has('admin.shifts.index') ? route('admin.shifts.index') : null,
                                        'shift_month' => \Illuminate\Support\Facades\Route::has('admin.shifts.month') ? route('admin.shifts.month') : null,
                                        'attendance_month' => \Illuminate\Support\Facades\Route::has('admin.attendances.index') ? route('admin.attendances.index') : null,
                                        'audit_logs' => \Illuminate\Support\Facades\Route::has('admin.attendance_logs.index') ? route('admin.attendance_logs.index') : null,
                                        'closings' => \Illuminate\Support\Facades\Route::has('admin.closings.index') ? route('admin.closings.index') : null,
                                        'attendance_intents' => \Illuminate\Support\Facades\Route::has('admin.attendance_intents.index') ? route('admin.attendance_intents.index') : null,
                                        'schools_master' => \Illuminate\Support\Facades\Route::has('admin.schools.index') ? route('admin.schools.index') : null,
                                        'bases_master' => \Illuminate\Support\Facades\Route::has('admin.bases.index') ? route('admin.bases.index') : null,
                                        'children_index' => \Illuminate\Support\Facades\Route::has('admin.children.index') ? route('admin.children.index') : null,
                                        'guardians_index' => \Illuminate\Support\Facades\Route::has('admin.guardians.index') ? route('admin.guardians.index') : null,
                                        'admin_users' => \Illuminate\Support\Facades\Route::has('admin.users.index') ? route('admin.users.index') : null,
                                    ];
                                    $featureLink = $featureLinks[$featureKey] ?? null;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-3 border text-left text-gray-800 font-semibold">
                                        @if($featureLink)
                                            <a href="{{ $featureLink }}" class="text-blue-600 hover:text-blue-800 underline">
                                                {{ $featureLabel }}
                                            </a>
                                        @else
                                            {{ $featureLabel }}
                                        @endif
                                    </td>
                                    @foreach($roles as $role)
                                        @php
                                            $row = $matrix[$role][$featureKey] ?? ['view'=>false,'create'=>false,'update'=>false,'delete'=>false];
                                        @endphp
                                        <td class="py-2 px-2 border text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $role }}][{{ $featureKey }}][view]"
                                                   value="1"
                                                   @checked($row['view'])>
                                        </td>
                                        <td class="py-2 px-2 border text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $role }}][{{ $featureKey }}][create]"
                                                   value="1"
                                                   @checked($row['create'])>
                                        </td>
                                        <td class="py-2 px-2 border text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $role }}][{{ $featureKey }}][update]"
                                                   value="1"
                                                   @checked($row['update'])>
                                        </td>
                                        <td class="py-2 px-2 border text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $role }}][{{ $featureKey }}][delete]"
                                                   value="1"
                                                   @checked($row['delete'])>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4 flex items-center justify-end">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2 rounded-md
                                       bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                            保存
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        (() => {
            const input = document.getElementById('registration_gate_password');
            const toggle = document.getElementById('toggle-registration-gate-password');
            const openIcon = document.getElementById('registration-eye-open');
            const closedIcon = document.getElementById('registration-eye-closed');

            if (!input || !toggle || !openIcon || !closedIcon) {
                return;
            }

            toggle.addEventListener('click', () => {
                const hidden = input.type === 'password';
                input.type = hidden ? 'text' : 'password';
                openIcon.classList.toggle('hidden', !hidden);
                closedIcon.classList.toggle('hidden', hidden);
            });
        })();
    </script>
</x-app-layout>
