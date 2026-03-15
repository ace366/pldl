<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminMessageNotificationController extends Controller
{
    public function unreadCount(Request $request)
    {
        $chatUnread = 0;
        $hasChatUnread = Schema::hasTable('chat_threads') && Schema::hasColumn('chat_threads', 'unread_count_staff');
        if ($hasChatUnread) {
            $chatUnread = (int) ChatThread::query()->sum('unread_count_staff');
            // 現在運用では admin/chats を正系とするため、重複計上を避ける
            return response()->json(['count' => $chatUnread]);
        }

        $legacyUnread = 0;
        $adminReadKey = null;
        if (Schema::hasTable('family_message_admin_reads')) {
            if (Schema::hasColumn('family_message_admin_reads', 'family_message_id')) {
                $adminReadKey = 'family_message_id';
            } elseif (Schema::hasColumn('family_message_admin_reads', 'message_id')) {
                $adminReadKey = 'message_id';
            }
        }

        if (!$adminReadKey) {
            return response()->json(['count' => $chatUnread]);
        }

        $query = \App\Models\FamilyMessage::query();

        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $query->where('sender_type', 'family');
        }

        $query->whereNotExists(function ($q) use ($adminReadKey) {
            $q->from('family_message_admin_reads as fmr')
                ->whereColumn("fmr.{$adminReadKey}", 'family_messages.id');
        });

        $legacyUnread = (int) $query->count();
        $count = $chatUnread + $legacyUnread;

        return response()->json(['count' => $count]);
    }
}
