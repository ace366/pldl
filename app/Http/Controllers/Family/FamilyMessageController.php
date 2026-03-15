<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\FamilyMessage;
use App\Models\FamilyMessageAdminRead;
use App\Models\FamilyMessageRead;
use App\Services\Line\LineApiService;
use App\Support\FamilyChildContext;
use App\Support\FamilyGuardianResolver;
use App\Support\FamilyMessageChildScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FamilyMessageController extends Controller
{
    private function resolveActiveChildId(Request $request): int
    {
        $ctx = FamilyChildContext::resolve($request);
        return (int)$ctx['activeChild']->id;
    }

    public function unreadCount(Request $request)
    {
        $childIds = FamilyMessageChildScope::forFamily($request);

        if (empty($childIds)) {
            abort(403);
        }

        $count = FamilyMessage::query()
            ->whereIn('child_id', $childIds)
            ->when(Schema::hasColumn('family_messages', 'sender_type'), function ($q) {
                $q->where('sender_type', 'admin');
            })
            ->whereNotExists(function ($q) use ($childIds) {
                $q->from('family_message_reads as r')
                    ->whereColumn('r.family_message_id', 'family_messages.id')
                    ->whereIn('r.child_id', $childIds);
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function readStatus(Request $request)
    {
        $childIds = FamilyMessageChildScope::forFamily($request);

        if (empty($childIds)) {
            abort(403);
        }

        $readIds = FamilyMessageAdminRead::query()
            ->whereIn('child_id', $childIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();

        return response()->json(['readIds' => $readIds]);
    }

    public function latest(Request $request)
    {
        $childIds = FamilyMessageChildScope::forFamily($request);
        if (empty($childIds)) {
            abort(403);
        }

        $afterId = max((int)$request->query('after_id', 0), 0);

        $messages = FamilyMessage::query()
            ->whereIn('child_id', $childIds)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['messages' => []]);
        }

        $messageIds = $messages->pluck('id')->all();
        $familyReadIds = FamilyMessageRead::query()
            ->whereIn('child_id', $childIds)
            ->whereIn('family_message_id', $messageIds)
            ->pluck('family_message_id')
            ->all();
        $adminReadIds = FamilyMessageAdminRead::query()
            ->whereIn('child_id', $childIds)
            ->whereIn('family_message_id', $messageIds)
            ->pluck('family_message_id')
            ->all();

        $familyReadSet = array_fill_keys($familyReadIds, true);
        $adminReadSet = array_fill_keys($adminReadIds, true);

        return response()->json([
            'messages' => $messages
                ->map(fn (FamilyMessage $message) => $this->toMessagePayload($message, $familyReadSet, $adminReadSet))
                ->values(),
        ]);
    }

    public function index(Request $request)
    {
        $childIds = FamilyMessageChildScope::forFamily($request);
        if (empty($childIds)) {
            abort(403);
        }

        $messages = FamilyMessage::query()
            ->whereIn('child_id', $childIds)
            ->latest('id')
            ->paginate(20);

        // ✅ DBは family_message_id
        $readIds = FamilyMessageRead::query()
            ->whereIn('child_id', $childIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();

        $readSet = array_fill_keys($readIds, true);

        return view('family.messages.index', [
            'messages' => $messages,
            'readSet'  => $readSet, // blade側で既読判定に使う
        ]);
    }

    public function markRead(Request $request, FamilyMessage $message)
    {
        $childId = $this->resolveActiveChildId($request);
        $childIds = FamilyMessageChildScope::forFamily($request);

        if ($childId <= 0 || empty($childIds)) {
            abort(403);
        }
        abort_unless(in_array((int)$message->child_id, $childIds, true), 403);

        foreach ($childIds as $targetChildId) {
            FamilyMessageRead::updateOrCreate(
                [
                    'family_message_id' => $message->id,
                    'child_id'          => $targetChildId,
                ],
                [
                    'read_at' => now(),
                ]
            );

            // ✅ 管理者側の既読も同時に付与（双方で既読状態を共有）
            FamilyMessageAdminRead::updateOrCreate(
                [
                    'family_message_id' => $message->id,
                    'child_id'          => $targetChildId,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('family.home', ['child_id' => $childId])
            ->with('success', '既読にしました。');
    }

    public function reply(Request $request, LineApiService $lineApi)
    {
        $childId = $this->resolveActiveChildId($request);

        if (!$childId) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $payload = [
            'child_id' => $childId,
            'title' => null,
            'body' => $validated['body'],
        ];

        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $payload['sender_type'] = 'family';
        }

        $message = FamilyMessage::create($payload);
        $this->syncToChatThread($request, $childId, (string) $validated['body']);
        if ((bool) config('features.line_integration_enabled', false)) {
            $this->syncToLineTimeline($request, $childId, (string) $validated['body'], $lineApi);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $this->toMessagePayload($message),
            ], 201);
        }

        return redirect()
            ->route('family.home', ['child_id' => $childId])
            ->with('success', '送信しました。');
    }

    /**
     * @param array<int, bool> $familyReadSet
     * @param array<int, bool> $adminReadSet
     * @return array<string, mixed>
     */
    private function toMessagePayload(
        FamilyMessage $message,
        array $familyReadSet = [],
        array $adminReadSet = []
    ): array {
        $sentAt = $message->published_at ?: $message->created_at;
        $from = (string)($message->sender_type ?: 'admin');
        $isRead = $from === 'family'
            ? isset($adminReadSet[$message->id])
            : isset($familyReadSet[$message->id]);

        return [
            'id' => (int)$message->id,
            'title' => $message->title ?: null,
            'body' => (string)$message->body,
            'sentAt' => optional($sentAt)->format('Y-m-d H:i'),
            'isRead' => $isRead,
            'readUrl' => route('family.messages.read', [
                'message' => (int)$message->id,
                'child_id' => (int)$message->child_id,
            ]),
            'from' => $from,
        ];
    }

    private function syncToChatThread(Request $request, int $childId, string $body): void
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

        $guardian = FamilyGuardianResolver::resolve($request, $childId);
        if (!$guardian) {
            return;
        }

        try {
            DB::transaction(function () use ($guardian, $body) {
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
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to sync family app message to chat thread', [
                'child_id' => $childId,
                'guardian_id' => (int) $guardian->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncToLineTimeline(Request $request, int $childId, string $body, LineApiService $lineApi): void
    {
        if ($childId <= 0 || trim($body) === '') {
            return;
        }

        $guardian = FamilyGuardianResolver::resolve($request, $childId);
        $lineUserId = trim((string) ($guardian?->line_user_id ?? ''));
        if ($lineUserId === '') {
            return;
        }

        try {
            $lineApi->pushText($lineUserId, $body);
        } catch (\Throwable $e) {
            Log::warning('Failed to mirror family app message to LINE timeline', [
                'child_id' => $childId,
                'guardian_id' => (int) ($guardian?->id ?? 0),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
