{{-- resources/views/admin/attendance_logs/index.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $logs  = $logs ?? ($items ?? ($rows ?? collect()));
    $bases = $bases ?? collect();
    $users = $users ?? collect();

    // フィルタ（無ければリクエスト値で表示だけ整える）
    $baseId = (int)($baseId ?? request('base_id', 0));
    $month  = (string)($month  ?? request('month', now()->format('Y-m')));
    $q      = (string)($q      ?? request('q', ''));

    $baseName = optional($bases->firstWhere('id', $baseId))->name ?? ($baseId ? "拠点#{$baseId}" : '全拠点');

    $displayUser = function($u) {
        if (!$u) return '-';
        $name = trim((string)($u->last_name ?? '').' '.(string)($u->first_name ?? ''));
        return $name !== '' ? $name : ($u->name ?? $u->email ?? ('ユーザー#'.($u->id ?? '?')));
    };

    $shortJson = function($v) {
        if ($v === null) return '';
        if (is_string($v)) return Str::limit($v, 160);
        try {
            return Str::limit(json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), 160);
        } catch (\Throwable $e) {
            return Str::limit((string)$v, 160);
        }
    };

    // minutes（数値/文字列）を "H:MM" に
    $minutesToLabel = function($minutes) {
        if ($minutes === null || $minutes === '') return '-';
        if (is_string($minutes) && !is_numeric($minutes)) return (string)$minutes;

        $m = (float)$minutes;
        if ($m < 0) $m = 0;

        // 小数も許容（分として扱い四捨五入）
        $mInt = (int)round($m);

        $h = intdiv($mInt, 60);
        $mm = $mInt % 60;
        return sprintf('%d:%02d', $h, $mm);
    };

    $viaLabel = function($via) {
        $via = (string)$via;
        return match ($via) {
            'qr'     => 'QR',
            'staff'  => 'スタッフ画面',
            'admin'  => '管理画面',
            'system' => 'システム',
            default  => $via !== '' ? $via : '-',
        };
    };

    // action を日本語へ
    $actionLabel = function($action) {
        $action = (string)$action;
        return match ($action) {
            'clock_in'  => '出勤',
            'clock_out' => '退勤',
            'update'    => '修正',
            'lock'      => '締め',
            'unlock'    => '解除',
            default     => $action !== '' ? $action : '-',
        };
    };

    $fmtDateTime = function($dt) {
        if (!$dt) return '-';
        try {
            return Carbon::parse($dt)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string)$dt;
        }
    };

    // payload から「変更（概要）」を作る
    $buildChangeSummary = function($payload, $shortJson, $minutesToLabel, $viaLabel) {
        if ($payload === null) return '—';

        // 文字列JSONを配列へ
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) $payload = $decoded;
        }

        if (is_array($payload)) {
            $parts = [];

            if (array_key_exists('via', $payload)) {
                $parts[] = '経路:'.$viaLabel($payload['via']);
            }
            if (array_key_exists('work_minutes', $payload)) {
                $parts[] = '勤務:'.$minutesToLabel($payload['work_minutes']);
            }
            if (array_key_exists('auto_break_minutes', $payload)) {
                $parts[] = '自動休憩:'.$minutesToLabel($payload['auto_break_minutes']);
            }

            $before = $payload['before'] ?? null;
            $after  = $payload['after']  ?? null;
            if (is_array($before) && is_array($after)) {
                $keys = ['clock_in_at','clock_out_at','status','work_minutes','auto_break_minutes','note','break_minutes'];
                $diffs = [];
                foreach ($keys as $k) {
                    $b = $before[$k] ?? null;
                    $a = $after[$k] ?? null;

                    if (in_array($k, ['work_minutes','auto_break_minutes','break_minutes'], true)) {
                        $b = $minutesToLabel($b);
                        $a = $minutesToLabel($a);
                    }

                    if ((string)$b !== (string)$a) {
                        $diffs[] = $k.':'.(string)$b.'→'.(string)$a;
                    }
                }
                if (!empty($diffs)) {
                    $parts[] = '差分:'.implode(' / ', $diffs);
                }
            }

            if (!empty($parts)) return implode(' / ', $parts);

            $sj = $shortJson($payload);
            return $sj !== '' ? $sj : '—';
        }

        $sj = $shortJson($payload);
        return $sj !== '' ? $sj : '—';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                勤怠ログ（修正履歴）
            </h2>

            @if(Route::has('admin.attendances.index'))
                <a href="{{ route('admin.attendances.index', ['base_id' => $baseId ?: null, 'month' => $month]) }}"
                   class="inline-flex items-center rounded-lg bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    ← 勤怠（月次）へ
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 説明 --}}
            <div class="mb-4 text-sm text-slate-600">
                管理者による修正履歴（理由・変更前後）を確認できます。
            </div>

            {{-- フィルタ --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
                <form method="GET"
                      action="{{ Route::has('admin.attendance_logs.index') ? route('admin.attendance_logs.index') : url()->current() }}"
                      class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">

                    <div>
                        <label for="base_id" class="block text-sm font-semibold text-slate-700">会場（拠点）</label>
                        <select id="base_id" name="base_id"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            <option value="0" @selected($baseId===0)>（全拠点）</option>
                            @foreach($bases as $b)
                                <option value="{{ $b->id }}" @selected((int)$b->id === $baseId)>
                                    {{ $b->name ?? ('拠点#'.$b->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="month" class="block text-sm font-semibold text-slate-700">月</label>
                        <input id="month" name="month" type="month" value="{{ $month }}"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div>
                        <label for="q" class="block text-sm font-semibold text-slate-700">キーワード（理由/メモ等）</label>
                        <input id="q" name="q" type="text" value="{{ $q }}"
                               placeholder="例：打刻漏れ / 遅刻 など"
                               class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 inline-flex justify-center items-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 active:scale-[0.99]">
                            表示する
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-sm text-slate-600">
                    表示中：<span class="font-bold">{{ $baseName }}</span> / <span class="font-bold">{{ $month }}</span>
                </div>
            </div>

            {{-- 一覧 --}}
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="font-semibold">ログ一覧</div>
                    <div class="text-xs text-slate-500">
                        @if(method_exists($logs,'total'))
                            件数：{{ $logs->total() }}
                        @else
                            件数：{{ is_countable($logs) ? count($logs) : '' }}
                        @endif
                    </div>
                </div>

                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-slate-500">
                            <tr>
                                <th class="py-2 pr-4">日時</th>
                                <th class="py-2 pr-4">拠点</th>
                                <th class="py-2 pr-4">対象スタッフ</th>
                                <th class="py-2 pr-4">操作</th>
                                <th class="py-2 pr-4">修正理由</th>
                                <th class="py-2 pr-4">変更（概要）</th>
                                <th class="py-2 pr-4">実行者</th>
                                <th class="py-2 pr-4">IP</th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                            @forelse($logs as $log)
                                @php
                                    $logBaseId   = (int)(data_get($log,'base_id') ?? 0);
                                    $logBaseName = optional($bases->firstWhere('id',$logBaseId))->name ?? ($logBaseId ? "拠点#{$logBaseId}" : '-');

                                    $targetUser = data_get($log,'user');
                                    $actorUser  = data_get($log,'actor');

                                    $payload = data_get($log,'payload');
                                    if (is_string($payload)) {
                                        $decoded = json_decode($payload, true);
                                        if (json_last_error() === JSON_ERROR_NONE) $payload = $decoded;
                                    }

                                    $reason = data_get($payload,'reason') ?? data_get($log,'reason') ?? '';

                                    $changeSummary = $buildChangeSummary($payload, $shortJson, $minutesToLabel, $viaLabel);

                                    $action = (string)(data_get($log,'action') ?? '');
                                    $actionText = $actionLabel($action);
                                @endphp

                                <tr>
                                    <td class="py-3 pr-4 whitespace-nowrap">
                                        {{ $fmtDateTime(data_get($log,'occurred_at')) }}
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap">{{ $logBaseName }}</td>
                                    <td class="py-3 pr-4">
                                        {{ $targetUser ? $displayUser($targetUser) : ('ユーザー#'.(data_get($log,'user_id') ?? '?')) }}
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold">
                                            {{ $actionText }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 max-w-[22rem]">
                                        <div class="line-clamp-2">{{ $reason }}</div>
                                    </td>
                                    <td class="py-3 pr-4 max-w-[28rem]">
                                        <div class="line-clamp-2 text-slate-700">
                                            {{ $changeSummary !== '' ? $changeSummary : '—' }}
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ $actorUser ? $displayUser($actorUser) : ('ユーザー#'.(data_get($log,'actor_user_id') ?? '-')) }}
                                    </td>
                                    <td class="py-3 pr-4 whitespace-nowrap">{{ (string)(data_get($log,'ip_address') ?? '-') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-slate-600">
                                        ログがありません。
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- paginate --}}
                    @if(method_exists($logs,'links'))
                        <div class="mt-6">
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
