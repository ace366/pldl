<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClosing;
use App\Models\Base;
use App\Services\RolePermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceClosingController extends Controller
{
    /**
     * 月次締め一覧
     * /admin/closings?base_id=...&month=YYYY-MM
     */
    public function index(Request $request)
    {
        $this->ensurePermission($request, 'closings', 'view');

        $month  = (string)$request->query('month', Carbon::now()->format('Y-m'));
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()->orderBy('id')->get();
        if ($baseId <= 0) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $closing = null;
        if ($baseId > 0) {
            $closing = AttendanceClosing::query()
                ->where('base_id', $baseId)
                ->where('year_month', $month)
                ->first();
        }

        $recent = collect();
        if ($baseId > 0) {
            $recent = AttendanceClosing::query()
                ->where('base_id', $baseId)
                ->orderByDesc('year_month')
                ->limit(12)
                ->get();
        }

        // ✅ 解除後でも「締め中/解除済み」を正しく表示するための判定
        $isClosed = false;
        if ($baseId > 0) {
            $isClosed = AttendanceClosing::isClosed($baseId, $month);
        }

        return view('admin.closings.index', [
            'month'    => $month,
            'baseId'   => $baseId,
            'bases'    => $bases,
            'closing'  => $closing,
            'recent'   => $recent,
            'isClosed' => $isClosed,
        ]);
    }

    /**
     * 月次締め実行（ロック）
     * POST /admin/closings/close
     */
    public function close(Request $request)
    {
        $this->ensurePermission($request, 'closings', 'update');

        $data = $request->validate([
            'base_id'    => ['required', 'integer', 'min:1'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $baseId = (int)$data['base_id'];
        $ym     = (string)$data['year_month'];

        DB::transaction(function () use ($request, $baseId, $ym) {
            // ✅ 再締めでも closed_at を必ず更新（解除情報はリセット）
            AttendanceClosing::query()
                ->updateOrCreate(
                    ['base_id' => $baseId, 'year_month' => $ym],
                    [
                        'closed_at'   => now(),
                        'closed_by'   => $request->user()->id,
                        'reopened_at' => null,
                        'reopened_by' => null,
                    ]
                );

            $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->toDateString();
            $end   = Carbon::createFromFormat('Y-m', $ym)->endOfMonth()->toDateString();

            // shift_attendances をロック
            DB::table('shift_attendances')
                ->where('base_id', $baseId)
                ->whereBetween('attendance_date', [$start, $end])
                ->update([
                    'is_locked'  => 1,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.closings.index', ['base_id' => $baseId, 'month' => $ym])
            ->with('success', "{$ym} を締め処理しました。");
    }

    /**
     * 月次締め解除（再オープン）
     * POST /admin/closings/open
     */
    public function open(Request $request)
    {
        $this->ensurePermission($request, 'closings', 'update');

        $data = $request->validate([
            'base_id'    => ['required', 'integer', 'min:1'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $baseId = (int)$data['base_id'];
        $ym     = (string)$data['year_month'];

        DB::transaction(function () use ($request, $baseId, $ym) {
            $closing = AttendanceClosing::query()
                ->where('base_id', $baseId)
                ->where('year_month', $ym)
                ->first();

            $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth()->toDateString();
            $end   = Carbon::createFromFormat('Y-m', $ym)->endOfMonth()->toDateString();

            // shift_attendances のロック解除
            DB::table('shift_attendances')
                ->where('base_id', $baseId)
                ->whereBetween('attendance_date', [$start, $end])
                ->update([
                    'is_locked'  => 0,
                    'updated_at' => now(),
                ]);

            // closing があれば reopened を記録（削除ではなく履歴を残す）
            if ($closing) {
                $closing->reopened_at = now();
                $closing->reopened_by = $request->user()->id;
                $closing->save();
            }
        });

        return redirect()
            ->route('admin.closings.index', ['base_id' => $baseId, 'month' => $ym])
            ->with('success', "{$ym} の締めを解除しました。");
    }

    // ---------------------------------------------------------------------
    // 内部
    // ---------------------------------------------------------------------
    private function ensurePermission(Request $request, string $feature, string $action): void
    {
        $user = $request->user();
        if (!RolePermissionService::canUser($user, $feature, $action)) {
            abort(403);
        }
    }
}
