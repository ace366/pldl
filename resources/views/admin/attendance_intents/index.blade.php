<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                @php
                    $canEdit = \App\Services\RolePermissionService::canUser(auth()->user(), 'attendance_intents', 'update');
                    $canMarkAbsentRole = in_array((string)(auth()->user()->role ?? ''), ['admin', 'staff'], true);
                    try {
                        $isTodayView = \Illuminate\Support\Carbon::parse($date)->isToday();
                    } catch (\Exception $e) {
                        $isTodayView = false;
                    }
                @endphp

                <h1 class="text-lg font-semibold mb-4">
                    参加予定・送迎管理
                </h1>

                {{-- フラッシュ --}}
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        <div class="font-semibold mb-1">入力エラー</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 日付選択 --}}
                <form method="GET" class="mb-6 flex items-center gap-2">
                    <input
                        type="date"
                        name="date"
                        value="{{ $date }}"
                        class="border rounded px-3 py-2"
                    >
                    <button class="px-4 py-2 bg-indigo-600 text-black rounded">
                        表示
                    </button>
                </form>

                {{-- 学校ごと --}}
                @foreach($summary as $data)
                    <div class="mb-8 border rounded-lg p-4">
                        <h2 class="font-bold text-base mb-2">
                            {{ $data['school']->name }}
                        </h2>

                        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                            <div>参加予定：<b>{{ $data['total'] }}人</b></div>
                            <div>送迎人数：<b>{{ $data['pickup'] }}人</b></div>
                            <div>必要車両：<b class="text-indigo-600">{{ $data['cars'] }}台</b></div>
                        </div>

                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-2 py-2 text-left">氏名</th>
                                    <th class="border px-2 py-2 text-left">状態</th>
                                    <th class="border px-2 py-2 text-left w-[80px]">送迎</th>
                                    <th class="border px-2 py-2 text-left w-[260px]">手動切替</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['children'] as $intent)
                                    @php
                                        // 自動判定（attendanceがあるか）
                                        $autoArrived = !$data['notArrived']->contains('child_id', $intent->child_id);

                                        // 手動優先
                                        if ($intent->manual_status === 'arrived') {
                                            $arrived = true;
                                        } elseif ($intent->manual_status === 'not_arrived') {
                                            $arrived = false;
                                        } else {
                                            $arrived = $autoArrived;
                                        }

                                        // 表示用ラベル
                                        $isAbsent = $intent->manual_status === 'not_arrived';
                                        $statusLabel = $arrived ? '出席済' : ($isAbsent ? '欠席' : '未到着');

                                        // どのモードで表示されているか（ボタンの強調用）
                                        $mode = $intent->manual_status ?? 'auto'; // arrived / not_arrived / auto(null)
                                    @endphp
                                    @php
                                        $pickupConfirmed = (bool)$intent->pickup_confirmed;
                                        $canMarkAbsent = $canEdit
                                            && $canMarkAbsentRole
                                            && $isTodayView
                                            && !$arrived
                                            && !$isAbsent
                                            && !$pickupConfirmed;

                                        $rowClass = '';
                                        if (!$arrived) {
                                            $rowClass = 'bg-red-100 text-red-700 font-bold';
                                        } elseif (!$pickupConfirmed) {
                                            $rowClass = 'bg-orange-50';
                                        }
                                    @endphp

                                    <tr class="{{ $rowClass }}">
                                        <td class="border px-2 py-2">
                                            <div class="font-semibold text-gray-900">
                                                {{ $intent->child->full_name }}
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                {{ trim(($intent->child->last_name_kana ?? '').' '.($intent->child->first_name_kana ?? '')) ?: '—' }}
                                            </div>
                                        </td>

                                        <td class="border px-2 py-2">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $statusLabel }}</span>

                                                {{-- いま手動ならバッジ --}}
                                                @if($isAbsent)
                                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 border border-red-300 text-red-800 font-semibold">
                                                        管理欠席
                                                    </span>
                                                @elseif($intent->manual_status)
                                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-yellow-100 border border-yellow-300 text-yellow-800 font-semibold">
                                                        手動
                                                    </span>
                                                @else
                                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 border border-gray-200 text-gray-600 font-semibold">
                                                        自動
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="border px-2 py-2">
                                            @php
                                                $pickupConfirmed = (bool)$intent->pickup_confirmed;
                                                $rowClass = '';

                                                if (!$arrived) {
                                                    $rowClass = 'bg-red-100 text-red-700 font-bold';
                                                } elseif (!$pickupConfirmed) {
                                                    // ✅ 到着済だけど未乗車（送迎が進んでない）を目立たせる
                                                    $rowClass = 'bg-orange-50';
                                                }
                                            @endphp


                                            @if($canEdit)
                                                <form method="POST" action="{{ route('admin.attendance_intents.toggle_pickup') }}">
                                                    @csrf
                                                    <input type="hidden" name="intent_id" value="{{ $intent->id }}">

                                                    <button type="submit"
                                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border px-3 py-2 transition
                                                            {{ $pickupConfirmed
                                                                ? 'bg-indigo-600 border-indigo-700 text-white shadow'
                                                                : 'bg-orange-50 border-orange-300 text-orange-900 hover:bg-orange-100' }}"
                                                        aria-label="乗車確認">
                                                        {{-- 車アイコン（未乗車は薄め） --}}
                                                            <img
                                                                src="{{ asset($pickupConfirmed ? 'images/ccar.png' : 'images/car.png') }}"
                                                                alt="{{ $pickupConfirmed ? '済' : '未' }}"
                                                                class="w-6 h-6 object-contain {{ $pickupConfirmed ? '' : 'opacity-60' }}"
                                                            >


                                                        <span class="text-xs font-extrabold">
                                                            {{ $pickupConfirmed ? '済' : '未' }}
                                                        </span>
                                                    </button>

                                                    @if($intent->pickup_confirmed_at)
                                                        <div class="mt-1 text-[11px] text-gray-500 font-normal text-center">
                                                            {{ \Illuminate\Support\Carbon::parse($intent->pickup_confirmed_at)->format('H:i') }}
                                                        </div>
                                                    @endif
                                                </form>
                                            @else
                                                <div class="w-full inline-flex items-center justify-center gap-2 rounded-lg border px-3 py-2
                                                            {{ $pickupConfirmed
                                                                ? 'bg-indigo-50 border-indigo-200 text-indigo-800'
                                                                : 'bg-orange-50 border-orange-200 text-orange-900' }}">
                                                    <img
                                                        src="{{ asset($pickupConfirmed ? 'images/ccar.png' : 'images/car.png') }}"
                                                        alt="{{ $pickupConfirmed ? '済' : '未' }}"
                                                        class="w-6 h-6 object-contain {{ $pickupConfirmed ? '' : 'opacity-60' }}"
                                                    >
                                                    <span class="text-xs font-extrabold">
                                                        {{ $pickupConfirmed ? '済' : '未' }}
                                                    </span>
                                                </div>
                                                @if($intent->pickup_confirmed_at)
                                                    <div class="mt-1 text-[11px] text-gray-500 font-normal text-center">
                                                        {{ \Illuminate\Support\Carbon::parse($intent->pickup_confirmed_at)->format('H:i') }}
                                                    </div>
                                                @endif
                                            @endif
                                        </td>


                                        <td class="border px-2 py-2">
                                            @if($canEdit)
                                                <div class="flex items-center gap-2">
                                                    {{-- 出席済にする --}}
                                                    <form method="POST" action="{{ route('admin.attendance_intents.toggle') }}">
                                                        @csrf
                                                        <input type="hidden" name="intent_id" value="{{ $intent->id }}">
                                                        <input type="hidden" name="manual_status" value="arrived">
                                                        <button type="submit"
                                                            class="px-3 py-1.5 rounded border
                                                                {{ $mode === 'arrived' ? 'bg-emerald-100 border-emerald-300 text-emerald-900' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                                            出席
                                                        </button>
                                                    </form>

                                                    @if($isTodayView)
                                                        <form method="POST"
                                                              action="{{ route('admin.attendance_intents.mark_absent') }}"
                                                              onsubmit="return confirm('この児童を欠席に変更しますか？');">
                                                            @csrf
                                                            <input type="hidden" name="intent_id" value="{{ $intent->id }}">
                                                            <button type="submit"
                                                                class="px-3 py-1.5 rounded border
                                                                    {{ $isAbsent ? 'bg-red-100 border-red-300 text-red-900' : 'bg-white border-red-300 text-red-700 hover:bg-red-50' }}"
                                                                @disabled(!$canMarkAbsent && !$isAbsent)>
                                                                {{ $isAbsent ? '欠席済' : '欠席にする' }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{-- 未到着にする --}}
                                                        <form method="POST" action="{{ route('admin.attendance_intents.toggle') }}">
                                                            @csrf
                                                            <input type="hidden" name="intent_id" value="{{ $intent->id }}">
                                                            <input type="hidden" name="manual_status" value="not_arrived">
                                                            <button type="submit"
                                                                class="px-3 py-1.5 rounded border
                                                                    {{ $mode === 'not_arrived' ? 'bg-red-100 border-red-300 text-red-900' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                                                未到着
                                                            </button>
                                                        </form>
                                                    @endif

                                                    {{-- 自動に戻す --}}
                                                    <form method="POST" action="{{ route('admin.attendance_intents.toggle') }}">
                                                        @csrf
                                                        <input type="hidden" name="intent_id" value="{{ $intent->id }}">
                                                        <input type="hidden" name="manual_status" value="auto">
                                                        <button type="submit"
                                                            class="px-3 py-1.5 rounded border
                                                                {{ $mode === 'auto' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                                            自動
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2 text-xs font-semibold">
                                                    <span class="px-3 py-1.5 rounded border
                                                        {{ $mode === 'arrived' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-white border-gray-200 text-gray-600' }}">
                                                        出席
                                                    </span>
                                                    <span class="px-3 py-1.5 rounded border
                                                        {{ $mode === 'not_arrived' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-white border-gray-200 text-gray-600' }}">
                                                        未到着
                                                    </span>
                                                    <span class="px-3 py-1.5 rounded border
                                                        {{ $mode === 'auto' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-600' }}">
                                                        自動
                                                    </span>
                                                </div>
                                            @endif

                                            @if($intent->manual_updated_at)
                                                <div class="mt-1 text-[11px] text-gray-500 font-normal">
                                                    最終更新：{{ \Illuminate\Support\Carbon::parse($intent->manual_updated_at)->format('Y/m/d H:i') }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                @if(($data['children'] ?? collect())->isEmpty())
                                    <tr>
                                        <td colspan="4" class="border px-2 py-6 text-center text-gray-500">
                                            この学校の参加予定はありません
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
</x-app-layout>
