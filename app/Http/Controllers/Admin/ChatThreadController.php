<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Child;
use App\Models\FamilyMessage;
use App\Services\Line\LineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ChatThreadController extends Controller
{
    /**
     * @var array<string, bool>
     */
    private static array $schemaColumnCache = [];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $unreadOnly = $request->boolean('unread_only');
        $status = (string) $request->query('status', '');
        $lineIntegrationEnabled = (bool) config('features.line_integration_enabled', false);
        if (!in_array($status, ['open', 'closed'], true)) {
            $status = '';
        }

        $hasUnreadCountStaff = $this->hasColumn('chat_threads', 'unread_count_staff');
        $guardianHasLastName = $this->hasColumn('guardians', 'last_name');
        $guardianHasFirstName = $this->hasColumn('guardians', 'first_name');
        $guardianHasName = $this->hasColumn('guardians', 'name');
        $guardianHasLineUserId = $lineIntegrationEnabled && $this->hasColumn('guardians', 'line_user_id');
        $childrenHasLastName = $this->hasColumn('children', 'last_name');
        $childrenHasFirstName = $this->hasColumn('children', 'first_name');
        $childrenHasCode = $this->hasColumn('children', 'child_code');
        $canSearchGuardian = $guardianHasLastName || $guardianHasFirstName || $guardianHasName || $guardianHasLineUserId;
        $canSearchChildren = $childrenHasLastName || $childrenHasFirstName || $childrenHasCode;

        $query = ChatThread::query()->with([
            'guardian',
            'guardian.children',
            'latestMessage',
        ]);

        if ($q !== '') {
            if ($canSearchGuardian || $canSearchChildren) {
                $query->where(function ($builder) use (
                    $q,
                    $guardianHasLastName,
                    $guardianHasFirstName,
                    $guardianHasName,
                    $guardianHasLineUserId,
                    $childrenHasLastName,
                    $childrenHasFirstName,
                    $childrenHasCode
                ) {
                    $builder->whereHas('guardian', function ($gq) use (
                        $q,
                        $guardianHasLastName,
                        $guardianHasFirstName,
                        $guardianHasName,
                        $guardianHasLineUserId,
                        $childrenHasLastName,
                        $childrenHasFirstName,
                        $childrenHasCode
                    ) {
                        $gq->where(function ($sq) use (
                            $q,
                            $guardianHasLastName,
                            $guardianHasFirstName,
                            $guardianHasName,
                            $guardianHasLineUserId,
                            $childrenHasLastName,
                            $childrenHasFirstName,
                            $childrenHasCode
                        ) {
                            $like = "%{$q}%";

                            if ($guardianHasLastName) {
                                $sq->orWhere('last_name', 'like', $like);
                            }
                            if ($guardianHasFirstName) {
                                $sq->orWhere('first_name', 'like', $like);
                            }
                            if ($guardianHasName) {
                                $sq->orWhere('name', 'like', $like);
                            }
                            if ($guardianHasLineUserId) {
                                $sq->orWhere('line_user_id', 'like', $like);
                            }

                            if ($childrenHasLastName || $childrenHasFirstName || $childrenHasCode) {
                                $sq->orWhereHas('children', function ($cq) use ($like, $childrenHasLastName, $childrenHasFirstName, $childrenHasCode) {
                                    if ($childrenHasLastName) {
                                        $cq->where('last_name', 'like', $like);
                                    }
                                    if ($childrenHasFirstName) {
                                        if ($childrenHasLastName) {
                                            $cq->orWhere('first_name', 'like', $like);
                                        } else {
                                            $cq->where('first_name', 'like', $like);
                                        }
                                    }
                                    if ($childrenHasLastName && $childrenHasFirstName) {
                                        $cq->orWhereRaw(
                                            "CONCAT(COALESCE(last_name,''), ' ', COALESCE(first_name,'')) like ?",
                                            [$like]
                                        );
                                    }
                                    if ($childrenHasCode) {
                                        $cq->orWhere('child_code', 'like', $like);
                                    }
                                });
                            }
                        });
                    });
                });
            } elseif (ctype_digit($q)) {
                $query->whereKey((int) $q);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($unreadOnly && $hasUnreadCountStaff) {
            $query->where('unread_count_staff', '>', 0);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($hasUnreadCountStaff) {
            $query->orderByRaw('CASE WHEN unread_count_staff > 0 THEN 0 ELSE 1 END');
        }

        $threads = $query
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate(30)
            ->withQueryString();

        $unreadChildren = $this->resolveUnreadChildren($q);

        return view('admin.chats.index', [
            'threads' => $threads,
            'unreadChildren' => $unreadChildren,
            'filters' => [
                'q' => $q,
                'unread_only' => $unreadOnly,
                'status' => $status,
            ],
            'lineIntegrationEnabled' => $lineIntegrationEnabled,
        ]);
    }

    public function show(Request $request, ChatThread $thread)
    {
        $thread->load([
            'guardian',
            'guardian.children',
        ]);

        $updates = [];
        if ($this->hasColumn('chat_threads', 'unread_count_staff')) {
            $updates['unread_count_staff'] = 0;
        }
        if ($this->hasColumn('chat_threads', 'last_staff_read_at')) {
            $updates['last_staff_read_at'] = now();
        }
        if ($updates !== []) {
            ChatThread::query()->whereKey($thread->id)->update($updates);
        }

        $thread->refresh();

        $messages = ChatMessage::query()
            ->with('sender:id,name')
            ->where('thread_id', $thread->id)
            ->orderBy('id')
            ->get();

        return view('admin.chats.show', [
            'thread' => $thread,
            'messages' => $messages,
            'lineIntegrationEnabled' => (bool) config('features.line_integration_enabled', false),
        ]);
    }

    public function reply(Request $request, ChatThread $thread, LineApiService $lineApi): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $staff = $request->user();

        $message = DB::transaction(function () use ($thread, $validated, $staff) {
            $message = ChatMessage::query()->create([
                'thread_id' => $thread->id,
                'sender_type' => 'staff',
                'sender_id' => (int) $staff->id,
                'body' => $validated['body'],
                'delivery_status' => 'pending',
            ]);

            $thread->last_message_at = now();
            $thread->status = 'open';
            if ($this->hasColumn('chat_threads', 'unread_count_staff')) {
                $thread->unread_count_staff = 0;
            }
            if ($this->hasColumn('chat_threads', 'last_staff_read_at')) {
                $thread->last_staff_read_at = now();
            }
            $thread->save();

            return $message;
        });

        $deliveryStatus = 'sent';
        $systemBody = null;
        $lineIntegrationEnabled = (bool) config('features.line_integration_enabled', false);

        if ($lineIntegrationEnabled) {
            $thread->loadMissing('guardian');
            $lineUserId = trim((string) ($thread->guardian->line_user_id ?? ''));
            if ($lineUserId === '') {
                $deliveryStatus = 'failed';
                $systemBody = '配信失敗: この保護者はLINE未連携です。';
            } else {
                try {
                    $lineApi->pushText($lineUserId, (string) $validated['body']);
                } catch (\Throwable $e) {
                    $deliveryStatus = 'failed';
                    $systemBody = '配信失敗: LINE push送信エラー（'.$e->getMessage().'）';
                    Log::warning('Staff chat push failed', [
                        'thread_id' => (int) $thread->id,
                        'chat_message_id' => (int) $message->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        DB::transaction(function () use ($message, $deliveryStatus, $systemBody, $thread) {
            $message->delivery_status = $deliveryStatus;
            $message->save();

            if ($systemBody !== null) {
                ChatMessage::query()->create([
                    'thread_id' => $thread->id,
                    'sender_type' => 'system',
                    'sender_id' => null,
                    'body' => $systemBody,
                    'delivery_status' => 'failed',
                ]);

                $thread->last_message_at = now();
                $thread->save();
            }
        });

        // 保護者アプリ（family_messages）にも同じ本文を反映
        $this->syncToFamilyMessages((int) $thread->guardian_id, (string) $validated['body'], (int) ($staff->id ?? 0));

        $flashKey = $deliveryStatus === 'sent' ? 'success' : 'line_link_error';
        $flashMessage = $deliveryStatus === 'sent'
            ? '返信を送信しました。'
            : '返信は保存しましたが、LINEへの配信に失敗しました。';

        return redirect()
            ->route('admin.chats.show', $thread)
            ->with($flashKey, $flashMessage);
    }

    public function messages(Request $request, ChatThread $thread): JsonResponse
    {
        $afterId = max((int) $request->query('after_id', 0), 0);

        $messages = ChatMessage::query()
            ->with('sender:id,name')
            ->where('thread_id', $thread->id)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($messages->isNotEmpty() || (int) ($thread->unread_count_staff ?? 0) > 0) {
            $updates = [];
            if ($this->hasColumn('chat_threads', 'unread_count_staff')) {
                $updates['unread_count_staff'] = 0;
            }
            if ($this->hasColumn('chat_threads', 'last_staff_read_at')) {
                $updates['last_staff_read_at'] = now();
            }
            if ($updates !== []) {
                ChatThread::query()->whereKey($thread->id)->update($updates);
            }
        }

        return response()->json([
            'messages' => $messages->map(function (ChatMessage $message) {
                return [
                    'id' => (int) $message->id,
                    'sender_type' => (string) $message->sender_type,
                    'body' => (string) $message->body,
                    'delivery_status' => (string) $message->delivery_status,
                    'sender_name' => (string) ($message->sender->name ?? ''),
                    'sent_at' => optional($message->created_at)->format('Y-m-d H:i'),
                ];
            })->values(),
        ]);
    }

    public function updateStatus(Request $request, ChatThread $thread): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $thread->status = (string) $validated['status'];
        $thread->save();

        return redirect()
            ->route('admin.chats.show', $thread)
            ->with('success', 'チャット状態を更新しました。');
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table.'.'.$column;
        if (array_key_exists($cacheKey, self::$schemaColumnCache)) {
            return self::$schemaColumnCache[$cacheKey];
        }

        self::$schemaColumnCache[$cacheKey] = Schema::hasTable($table) && Schema::hasColumn($table, $column);

        return self::$schemaColumnCache[$cacheKey];
    }

    private function resolveUnreadChildren(string $q = '')
    {
        $adminReadKey = null;
        if (Schema::hasTable('family_message_admin_reads')) {
            if (Schema::hasColumn('family_message_admin_reads', 'family_message_id')) {
                $adminReadKey = 'family_message_id';
            } elseif (Schema::hasColumn('family_message_admin_reads', 'message_id')) {
                $adminReadKey = 'message_id';
            }
        }

        $hasDeletedAt = $this->hasColumn('family_messages', 'deleted_at');
        $query = Child::query()
            ->with([
                'guardians:id,last_name,first_name',
            ])
            ->select('children.*')
            ->selectSub(function ($sub) use ($adminReadKey, $hasDeletedAt) {
                $sub->from('family_messages as fm')
                    ->whereColumn('fm.child_id', 'children.id');

                if ($hasDeletedAt) {
                    $sub->whereNull('fm.deleted_at');
                }

                if ($this->hasColumn('family_messages', 'sender_type')) {
                    $sub->where('fm.sender_type', 'family');
                }

                if ($adminReadKey) {
                    $sub->whereNotExists(function ($qq) use ($adminReadKey) {
                        $qq->from('family_message_admin_reads as fmr')
                            ->whereColumn("fmr.{$adminReadKey}", 'fm.id')
                            ->whereColumn('fmr.child_id', 'children.id');
                    });
                }

                $sub->selectRaw('COUNT(*)');
            }, 'unread_message_count')
            ->having('unread_message_count', '>', 0);

        $q = trim($q);
        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder
                    ->where('children.last_name', 'like', $like)
                    ->orWhere('children.first_name', 'like', $like)
                    ->orWhere('children.last_name_kana', 'like', $like)
                    ->orWhere('children.first_name_kana', 'like', $like)
                    ->orWhere('children.name', 'like', $like);

                if ($this->hasColumn('children', 'child_code')) {
                    $builder->orWhere('children.child_code', 'like', $like);
                }

                $builder->orWhereHas('guardians', function ($guardianQuery) use ($like) {
                    $guardianQuery->where('last_name', 'like', $like)
                        ->orWhere('first_name', 'like', $like);
                });
            });
        }

        $query->orderByDesc('unread_message_count');
        if ($this->hasColumn('children', 'child_code')) {
            $query->orderBy('children.child_code');
        }

        return $query
            ->orderBy('children.last_name')
            ->orderBy('children.first_name')
            ->limit(50)
            ->get();
    }

    private function syncToFamilyMessages(int $guardianId, string $body, int $staffId = 0): void
    {
        if ($guardianId <= 0 || trim($body) === '') {
            return;
        }
        if (!Schema::hasTable('family_messages') || !Schema::hasColumn('family_messages', 'child_id')) {
            return;
        }

        $childId = (int) DB::table('child_guardian')
            ->where('guardian_id', $guardianId)
            ->orderBy('child_id')
            ->value('child_id');

        if ($childId <= 0) {
            return;
        }

        $payload = [
            'child_id' => $childId,
            'title' => null,
            'body' => $body,
        ];
        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $payload['sender_type'] = 'admin';
        }
        if ($staffId > 0 && Schema::hasColumn('family_messages', 'created_by')) {
            $payload['created_by'] = $staffId;
        }

        try {
            FamilyMessage::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to sync chat message to family_messages', [
                'guardian_id' => $guardianId,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
