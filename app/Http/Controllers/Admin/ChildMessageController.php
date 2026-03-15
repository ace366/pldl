<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\FamilyMessage;
use App\Models\FamilyMessageAdminRead;
use App\Models\FamilyMessageRead;
use App\Support\FamilyGuardianResolver;
use App\Support\FamilyMessageChildScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChildMessageController extends Controller
{
    public function index(Child $child)
    {
        $canSend = \App\Services\RolePermissionService::canUser(Auth::user(), 'children_index', 'create');
        $messageChildIds = FamilyMessageChildScope::forChildId((int)$child->id);
        if (empty($messageChildIds)) {
            $messageChildIds = [(int)$child->id];
        }

        $messages = FamilyMessage::query()
            ->whereIn('child_id', $messageChildIds)
            ->latest('id')
            ->take(500)
            ->get()
            ->reverse()
            ->values();

        $adminReadIds = FamilyMessageAdminRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();
        $familyReadIds = FamilyMessageRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();

        $adminReadSet = array_fill_keys($adminReadIds, true);
        $familyReadSet = array_fill_keys($familyReadIds, true);

        $messagesPayload = $messages->map(function ($m) use ($adminReadSet, $familyReadSet) {
            $sentAt = $m->published_at ?: $m->created_at;
            $from = $m->sender_type ?: 'admin';
            $isRead = $from === 'admin'
                ? isset($familyReadSet[$m->id]) // 保護者が既読にしたか
                : isset($adminReadSet[$m->id]);  // 管理者が既読にしたか

            return [
                'id' => $m->id,
                'title' => $m->title ?: null,
                'body' => $m->body,
                'sentAt' => optional($sentAt)->format('Y-m-d H:i'),
                'isRead' => $isRead,
                'readUrl' => route('admin.children.messages.read', [$m->child_id, $m]),
                'from' => $from,
            ];
        })->values();

        $selectedGuardian = FamilyGuardianResolver::resolveForChild((int)$child->id);

        $adminProps = [
            'child' => [
                'name' => $child->full_name,
                'code' => $child->child_code,
                'school' => optional($child->school)->name ?? '—',
                'grade' => $child->grade ?? '—',
                'base' => optional($child->baseMaster)->name ?? (optional($child->base)->name ?? '—'),
            ],
            'messages' => $messagesPayload,
            'csrf' => csrf_token(),
            'parentAvatar' => route('admin.children.messages.parent_avatar', [
                'child' => $child,
                'v' => (int)optional($selectedGuardian?->updated_at)->timestamp,
            ]),
            'adminAvatar' => asset('images/512_512.jpg'),
            'sendUrl' => $canSend ? route('admin.children.messages.store', $child) : null,
            'canSend' => $canSend,
            'readStatusUrl' => route('admin.children.messages.read_status', $child),
            'fetchMessagesUrl' => route('admin.children.messages.latest', $child),
        ];

        return view('admin.children.messages', [
            'child' => $child,
            'adminProps' => $adminProps,
        ]);
    }

    public function store(Request $request, Child $child)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body'  => ['required', 'string', 'max:5000'],
        ]);

        $payload = [
            'child_id' => $child->id,
            'title'    => $request->input('title'),
            'body'     => $request->input('body'),
        ];

        // ✅ created_by カラムがある環境だけ入れる（本番事故防止）
        if (Schema::hasColumn('family_messages', 'created_by')) {
            $payload['created_by'] = auth()->id();
        }
        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $payload['sender_type'] = 'admin';
        }
        // もし created_by_user_id 等の別名ならここに追記（後述）

        $message = FamilyMessage::create($payload);

        if (request()->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $this->toMessagePayload($message),
            ], 201);
        }

        return redirect()
            ->route('admin.children.messages.index', $child)
            ->with('success', 'メッセージを送信しました。');
    }

    public function markRead(Request $request, Child $child, FamilyMessage $message)
    {
        $messageChildIds = FamilyMessageChildScope::forChildId((int)$child->id);
        if (empty($messageChildIds)) {
            $messageChildIds = [(int)$child->id];
        }
        abort_unless(in_array((int)$message->child_id, $messageChildIds, true), 404);

        foreach ($messageChildIds as $targetChildId) {
            FamilyMessageAdminRead::updateOrCreate(
                [
                    'family_message_id' => $message->id,
                    'child_id' => $targetChildId,
                ],
                [
                    'read_at' => now(),
                ]
            );

            // ✅ 保護者側の既読も同時に付与（双方で既読状態を共有）
            FamilyMessageRead::updateOrCreate(
                [
                    'family_message_id' => $message->id,
                    'child_id' => $targetChildId,
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
            ->route('admin.children.messages.index', $child);
    }

    public function readStatus(Request $request, Child $child)
    {
        $messageChildIds = FamilyMessageChildScope::forChildId((int)$child->id);
        if (empty($messageChildIds)) {
            $messageChildIds = [(int)$child->id];
        }

        $readIds = FamilyMessageRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->distinct()
            ->pluck('family_message_id')
            ->all();

        return response()->json(['readIds' => $readIds]);
    }

    public function latest(Request $request, Child $child)
    {
        $messageChildIds = FamilyMessageChildScope::forChildId((int)$child->id);
        if (empty($messageChildIds)) {
            $messageChildIds = [(int)$child->id];
        }

        $afterId = max((int)$request->query('after_id', 0), 0);

        $messages = FamilyMessage::query()
            ->whereIn('child_id', $messageChildIds)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['messages' => []]);
        }

        $messageIds = $messages->pluck('id')->all();
        $adminReadIds = FamilyMessageAdminRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->whereIn('family_message_id', $messageIds)
            ->pluck('family_message_id')
            ->all();
        $familyReadIds = FamilyMessageRead::query()
            ->whereIn('child_id', $messageChildIds)
            ->whereIn('family_message_id', $messageIds)
            ->pluck('family_message_id')
            ->all();

        $adminReadSet = array_fill_keys($adminReadIds, true);
        $familyReadSet = array_fill_keys($familyReadIds, true);

        return response()->json([
            'messages' => $messages
                ->map(fn (FamilyMessage $message) => $this->toMessagePayload($message, $adminReadSet, $familyReadSet))
                ->values(),
        ]);
    }

    public function parentAvatar(Request $request, Child $child)
    {
        $fallback = redirect(asset('images/parents.png'));
        if (!Schema::hasColumn('guardians', 'avatar_path')) {
            return $fallback;
        }

        $guardian = FamilyGuardianResolver::resolveForChild((int)$child->id);
        if (!$guardian) {
            return $fallback;
        }

        $path = trim((string)($guardian->avatar_path ?? ''));
        if ($path === '' || !Storage::disk('local')->exists($path)) {
            return $fallback;
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * @param array<int, bool> $adminReadSet
     * @param array<int, bool> $familyReadSet
     * @return array<string, mixed>
     */
    private function toMessagePayload(
        FamilyMessage $message,
        array $adminReadSet = [],
        array $familyReadSet = []
    ): array {
        $sentAt = $message->published_at ?: $message->created_at;
        $from = (string)($message->sender_type ?: 'admin');
        $isRead = $from === 'admin'
            ? isset($familyReadSet[$message->id])
            : isset($adminReadSet[$message->id]);

        return [
            'id' => (int)$message->id,
            'title' => $message->title ?: null,
            'body' => (string)$message->body,
            'sentAt' => optional($sentAt)->format('Y-m-d H:i'),
            'isRead' => $isRead,
            'readUrl' => route('admin.children.messages.read', [(int)$message->child_id, (int)$message->id]),
            'from' => $from,
        ];
    }
}
