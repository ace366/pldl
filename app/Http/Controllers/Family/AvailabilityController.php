<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Child;
use App\Models\FamilyMessage;
use App\Support\FamilyChildContext;
use App\Support\FamilyGuardianResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AvailabilityController extends Controller
{
    private const TODAY_CANCEL_REASON_LABELS = [
        'illness' => 'かぜ・けがで欠席',
        'family' => '家族の都合で欠席',
        'lesson' => '習い事があるため欠席',
        'other' => 'その他',
    ];

    public function index(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $child = $ctx['activeChild'];
        $siblings = $ctx['siblings'];

        // 4週間カレンダーの開始日（YYYY-MM-DD）
        // 指定がなければ「今週の日曜」から
        $startParam = $request->query('start');
        $gridStart = $startParam
            ? Carbon::createFromFormat('Y-m-d', $startParam)->startOfDay()
            : now()->startOfWeek(Carbon::SUNDAY)->startOfDay();

        // 4週間（28日）
        $gridEnd = $gridStart->copy()->addDays(27);

        $intents = DB::table('child_attendance_intents')
            ->where('child_id', $child->id)
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->pluck('pickup_required', 'date'); // ['YYYY-MM-DD' => 0/1]

        // ✅ React版ビューに切り替え（ここが重要）
        $siblingTabs = $siblings->map(function ($s) use ($gridStart, $child) {
            return [
                'id' => (int)$s->id,
                'name' => $s->full_name,
                'grade' => (int)($s->grade ?? 0),
                'isActive' => (int)$s->id === (int)$child->id,
                'availabilityUrl' => route('family.availability.index', [
                    'child_id' => (int)$s->id,
                    'start' => $gridStart->toDateString(),
                ]),
                'qrUrl' => route('family.child.qr', ['child_id' => (int)$s->id]),
            ];
        })->values();

        return view('family.availability_react', [
            'child'     => $child,
            'siblingTabs' => $siblingTabs,
            'gridStart' => $gridStart,
            'gridEnd'   => $gridEnd,
            'intents'   => $intents,
        ]);
    }

    public function toggle(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $childId = (int)$ctx['activeChild']->id;

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'cancel_reason' => ['nullable', 'in:illness,family,lesson,other'],
            'cancel_reason_other' => ['nullable', 'string', 'max:255'],
        ], [
            'date.required' => '日付が必要です。',
            'date.date_format' => '日付の形式が正しくありません。',
            'cancel_reason.in' => '解除理由が不正です。',
            'cancel_reason_other.max' => 'その他の理由は255文字以内で入力してください。',
        ]);

        $date = $validated['date'];

        // 未来・当日のみ許可（過去は不可）
        if (Carbon::parse($date)->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'ok' => false,
                'message' => '過去の日付は選べません。',
            ], 422);
        }

        $row = DB::table('child_attendance_intents')
            ->where('child_id', $childId)
            ->where('date', $date)
            ->first();

        if ($row) {
            $isToday = $date === now()->toDateString();
            $child = $ctx['activeChild'];
            $cancelReasonLabel = null;

            if ($isToday) {
                $reasonKey = (string)($validated['cancel_reason'] ?? '');
                if (!isset(self::TODAY_CANCEL_REASON_LABELS[$reasonKey])) {
                    return response()->json([
                        'ok' => false,
                        'message' => '本日の送迎を解除する場合は理由を選択してください。',
                    ], 422);
                }

                if ($reasonKey === 'other') {
                    $other = trim((string)($validated['cancel_reason_other'] ?? ''));
                    if ($other === '') {
                        return response()->json([
                            'ok' => false,
                            'message' => '「その他」を選んだ場合は理由を入力してください。',
                        ], 422);
                    }
                    $cancelReasonLabel = 'その他：' . $other;
                } else {
                    $cancelReasonLabel = self::TODAY_CANCEL_REASON_LABELS[$reasonKey];
                }
            }

            DB::transaction(function () use ($row, $isToday, $child, $date, $cancelReasonLabel) {
                DB::table('child_attendance_intents')
                    ->where('id', $row->id)
                    ->delete();

                if ($isToday && $cancelReasonLabel !== null) {
                    $this->postTodayCancelReasonMessage($child, $date, $cancelReasonLabel);
                }
            });

            return response()->json([
                'ok' => true,
                'status' => 'off',
                'date' => $date,
            ]);
        }

        DB::table('child_attendance_intents')->insert([
            'child_id' => $childId,
            'date' => $date,
            'pickup_required' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'status' => 'on',
            'date' => $date,
        ]);
    }

    /**
     * 一括ON/OFF
     * - ルート名は既存互換で bulk_on のまま
     * - payload 例:
     *   {
     *     mode: "grid" | "month",
     *     start: "YYYY-MM-DD",
     *     end: "YYYY-MM-DD",
     *     weekdays: [0..6],
     *     action: "on" | "off"
     *   }
     */
    public function bulkOn(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $childId = (int)$ctx['activeChild']->id;
        if ($childId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'セッションが無効です。再ログインしてください。',
            ], 401);
        }

        $validated = $request->validate([
            'mode'     => ['required', 'in:grid,month'],
            'start'    => ['required', 'date_format:Y-m-d'],
            'end'      => ['required', 'date_format:Y-m-d'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'action'   => ['nullable', 'in:on,off'],
        ], [
            'mode.required' => 'mode が必要です。',
            'mode.in' => 'mode が不正です。',
            'start.required' => 'start が必要です。',
            'start.date_format' => 'start の形式が不正です。',
            'end.required' => 'end が必要です。',
            'end.date_format' => 'end の形式が不正です。',
            'weekdays.required' => 'weekdays が必要です。',
            'weekdays.array' => 'weekdays の形式が不正です。',
            'weekdays.*.between' => 'weekdays の値が不正です。',
            'action.in' => 'action が不正です。',
        ]);

        $action = $validated['action'] ?? 'on';

        $start = Carbon::createFromFormat('Y-m-d', $validated['start'])->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $validated['end'])->startOfDay();

        if ($end->lt($start)) {
            return response()->json([
                'ok' => false,
                'message' => 'start と end の関係が不正です。',
            ], 422);
        }

        // 安全弁：範囲が極端に大きいときの暴走を防ぐ
        $maxDays = ($validated['mode'] === 'grid') ? 35 : 40;
        $diffDays = $start->diffInDays($end) + 1;
        if ($diffDays > $maxDays) {
            return response()->json([
                'ok' => false,
                'message' => '対象期間が大きすぎます。',
            ], 422);
        }

        $weekdays = array_values(array_unique(array_map('intval', $validated['weekdays'])));
        $weekdaySet = array_fill_keys($weekdays, true);

        $today = now()->startOfDay();

        // 対象日の配列を作る（未来のみ）
        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->gte($today)) {
                $wd = (int)$cursor->dayOfWeek; // 0=日
                if (isset($weekdaySet[$wd])) {
                    $dates[] = $cursor->toDateString();
                }
            }
            $cursor->addDay();
        }

        if (empty($dates)) {
            return response()->json([
                'ok' => true,
                'count' => 0,
                'action' => $action,
                'message' => '対象がありません。',
            ]);
        }

        try {
            $count = 0;

            DB::transaction(function () use ($childId, $dates, $action, &$count) {

                if ($action === 'off') {
                    $count = DB::table('child_attendance_intents')
                        ->where('child_id', $childId)
                        ->whereIn('date', $dates)
                        ->delete();
                    return;
                }

                $existing = DB::table('child_attendance_intents')
                    ->where('child_id', $childId)
                    ->whereIn('date', $dates)
                    ->pluck('date')
                    ->all();

                $existSet = array_fill_keys($existing, true);

                $rows = [];
                foreach ($dates as $d) {
                    if (isset($existSet[$d])) continue;
                    $rows[] = [
                        'child_id' => $childId,
                        'date' => $d,
                        'pickup_required' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $count = count($rows);

                if (!empty($rows)) {
                    DB::table('child_attendance_intents')->insertOrIgnore($rows);
                }
            });

            return response()->json([
                'ok' => true,
                'count' => $count,
                'action' => $action,
                'mode' => $validated['mode'],
                'start' => $validated['start'],
                'end' => $validated['end'],
                'weekdays' => $weekdays,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => '一括更新に失敗しました。',
            ], 500);
        }
    }

    private function postTodayCancelReasonMessage(Child $child, string $date, string $reason): void
    {
        $body = "本日の送迎を解除しました。\n日付：{$date}\n理由：{$reason}";
        $payload = [
            'child_id' => (int)$child->id,
            'title' => '本日の送迎解除',
            'body' => $body,
        ];

        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $payload['sender_type'] = 'family';
        }

        FamilyMessage::create($payload);
        $this->syncToChatThread((int) $child->id, $body);
    }

    private function syncToChatThread(int $childId, string $body): void
    {
        if ($childId <= 0 || trim($body) === '') {
            return;
        }
        if (!Schema::hasTable('chat_threads') || !Schema::hasTable('chat_messages')) {
            return;
        }
        if (!Schema::hasColumn('chat_messages', 'thread_id')
            || !Schema::hasColumn('chat_messages', 'sender_type')
            || !Schema::hasColumn('chat_messages', 'body')) {
            return;
        }

        $guardian = FamilyGuardianResolver::resolveForChild($childId);
        if (!$guardian) {
            return;
        }

        try {
            $thread = ChatThread::query()->firstOrCreate(
                ['guardian_id' => (int) $guardian->id],
                ['status' => 'open']
            );

            $payload = [
                'thread_id' => (int) $thread->id,
                'sender_type' => 'family',
                'body' => $body,
            ];
            if (Schema::hasColumn('chat_messages', 'sender_id')) {
                $payload['sender_id'] = null;
            }
            if (Schema::hasColumn('chat_messages', 'delivery_status')) {
                $payload['delivery_status'] = 'sent';
            }

            ChatMessage::query()->create($payload);

            $thread->last_message_at = now();
            $thread->status = 'open';
            if (Schema::hasColumn('chat_threads', 'unread_count_staff')) {
                $thread->unread_count_staff = (int) ($thread->unread_count_staff ?? 0) + 1;
            }
            $thread->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to sync attendance cancel message to chat thread', [
                'child_id' => $childId,
                'guardian_id' => (int) $guardian->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
