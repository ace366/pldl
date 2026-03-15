<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Base;
use App\Models\User;
use App\Services\RolePermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    /**
     * 監査ログ一覧
     * /admin/attendance-logs?base_id=...&month=YYYY-MM&user_id=...&action=...
     */
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $month  = (string)$request->query('month', Carbon::now()->format('Y-m'));
        $baseId = (int)$request->query('base_id', 0);
        $userId = $request->query('user_id');
        $action = trim((string)$request->query('action', ''));

        $bases = Base::query()->orderBy('id')->get();
        if ($baseId <= 0) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $q = AttendanceLog::query()
            ->where('base_id', $baseId);

        // monthフィルタ
        $q->whereRaw("DATE_FORMAT(occurred_at, '%Y-%m') = ?", [$month]);

        if (!is_null($userId) && $userId !== '') {
            $q->where('user_id', (int)$userId);
        }

        if ($action !== '') {
            $q->where('action', $action);
        }

        $logs = $q->with(['user', 'actor'])
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->appends($request->query());

        // ユーザー候補（選択用：その拠点×月に出現したユーザーだけ）
        $userIds = AttendanceLog::query()
            ->where('base_id', $baseId)
            ->whereRaw("DATE_FORMAT(occurred_at, '%Y-%m') = ?", [$month])
            ->select('user_id')
            ->distinct()
            ->pluck('user_id')
            ->all();

        $users = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('id')
            ->get();

        return view('admin.attendance_logs.index', [
            'month'  => $month,
            'baseId' => $baseId,
            'bases'  => $bases,
            'users'  => $users,
            'action' => $action,
            'logs'   => $logs,
        ]);
    }

    // ---------------------------------------------------------------------
    // 内部
    // ---------------------------------------------------------------------
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!RolePermissionService::canUser($user, 'audit_logs', 'view')) {
            abort(403);
        }
    }
}
