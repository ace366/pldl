<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminMessageNotificationController extends Controller
{
    public static function resolveUnreadCount(?User $user): int
    {
        $role = (string) ($user?->role ?? '');
        if (!in_array($role, ['admin', 'staff'], true)) {
            return 0;
        }

        $hasChatUnread = Schema::hasTable('chat_threads') && Schema::hasColumn('chat_threads', 'unread_count_staff');
        if ($hasChatUnread) {
            // admin/chats を正系として扱い、legacy メッセージとの重複計上を避ける
            return (int) ChatThread::query()->sum('unread_count_staff');
        }

        $adminReadKey = null;
        if (Schema::hasTable('family_message_admin_reads')) {
            if (Schema::hasColumn('family_message_admin_reads', 'family_message_id')) {
                $adminReadKey = 'family_message_id';
            } elseif (Schema::hasColumn('family_message_admin_reads', 'message_id')) {
                $adminReadKey = 'message_id';
            }
        }

        if (!$adminReadKey) {
            return 0;
        }

        $query = \App\Models\FamilyMessage::query();

        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $query->where('sender_type', 'family');
        }

        $query->whereNotExists(function ($q) use ($adminReadKey) {
            $q->from('family_message_admin_reads as fmr')
                ->whereColumn("fmr.{$adminReadKey}", 'family_messages.id');
        });

        return (int) $query->count();
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => self::resolveUnreadCount($request->user()),
        ]);
    }
}
