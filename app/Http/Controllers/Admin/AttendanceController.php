<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClosing;
use App\Models\AttendanceLog;
use App\Models\Base;
use App\Models\ShiftAttendance;
use App\Models\User;
use App\Services\RolePermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    /**
     * 日別一覧（拠点×日）
     * /admin/attendances?base_id=...&date=YYYY-MM-DD
     * 互換: month が来た場合は月初日へ寄せる
     */
    public function index(Request $request)
    {
        $this->ensurePermission($request, 'attendance_month', 'view');

        $dateParam = (string)$request->query('date', '');
        $monthParam = (string)$request->query('month', '');

        if ($dateParam === '' && $monthParam !== '') {
            $dateParam = $monthParam . '-01';
        }
        if ($dateParam === '') {
            $dateParam = Carbon::now()->toDateString();
        }

        $date = Carbon::parse($dateParam)->toDateString();
        $month = Carbon::parse($date)->format('Y-m');

        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()->orderBy('id')->get();
        if ($baseId <= 0) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $isClosed = $baseId > 0 ? AttendanceClosing::isClosed($baseId, $month) : false;

        $attendances = collect();
        if ($baseId > 0) {
            $attendances = ShiftAttendance::query()
                ->where('base_id', $baseId)
                ->forDate($date)
                ->with(['user', 'shift', 'base'])
                ->orderBy('attendance_date')
                ->orderBy('user_id')
                ->get();
        }

        return view('admin.attendances.index', [
            'date'        => $date,
            'month'       => $month,
            'baseId'      => $baseId,
            'bases'       => $bases,
            'attendances' => $attendances,
            'isClosed'    => $isClosed,
        ]);
    }

    /**
     * スタッフ別 月次詳細
     * /admin/attendances/user/{user}?base_id=...&month=YYYY-MM
     */
    public function userMonth(Request $request, User $user)
    {
        $this->ensurePermission($request, 'attendance_month', 'view');

        $month  = (string)$request->query('month', Carbon::now()->format('Y-m'));
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()->orderBy('id')->get();
        if ($baseId <= 0) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $isClosed = $baseId > 0 ? AttendanceClosing::isClosed($baseId, $month) : false;

        $items = collect();
        if ($baseId > 0) {
            $items = ShiftAttendance::query()
                ->where('base_id', $baseId)
                ->where('user_id', $user->id)
                ->forMonth($month)
                ->with(['shift'])
                ->orderBy('attendance_date')
                ->orderBy('id')
                ->get();
        }

        $totalMinutes = (int)$items->sum('work_minutes');

        return view('admin.attendances.user_month', [
            'month'       => $month,
            'baseId'      => $baseId,
            'bases'       => $bases,
            'user'        => $user,
            'items'       => $items,
            'totalMinutes'=> $totalMinutes,
            'totalLabel'  => $this->minutesToLabel($totalMinutes),
            'isClosed'    => $isClosed,
        ]);
    }

    /**
     * 修正画面
     */
    public function edit(Request $request, ShiftAttendance $shiftAttendance)
    {
        $this->ensurePermission($request, 'attendance_month', 'update');

        $ym = Carbon::parse($shiftAttendance->attendance_date)->format('Y-m');
        $isClosed = AttendanceClosing::isClosed((int)$shiftAttendance->base_id, $ym);

        return view('admin.attendances.edit', [
            'attendance' => $shiftAttendance->load(['user', 'shift', 'base']),
            'isClosed'   => $isClosed || (bool)$shiftAttendance->is_locked,
            'yearMonth'  => $ym,
        ]);
    }

    /**
     * 修正保存（必ずログを残す）
     */
    public function update(Request $request, ShiftAttendance $shiftAttendance)
    {
        $this->ensurePermission($request, 'attendance_month', 'update');

        // 締めチェック（物理ロック or closings）
        $this->ensureNotClosedOrLocked($shiftAttendance);

        $data = $request->validate([
            'clock_in_at'  => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date'],
            'break_minutes'=> ['nullable', 'integer', 'min:0', 'max:600'],
            'status'       => ['required', 'string', 'max:20'],
            'note'         => ['nullable', 'string'],
            'reason'       => ['required', 'string', 'max:255'], // 修正理由必須
        ]);

        DB::transaction(function () use ($request, $shiftAttendance, $data) {
            $before = $shiftAttendance->only([
                'clock_in_at', 'clock_out_at', 'status', 'work_minutes', 'auto_break_minutes', 'break_minutes', 'note'
            ]);

            $shiftAttendance->clock_in_at  = $data['clock_in_at'] ?? null;
            $shiftAttendance->clock_out_at = $data['clock_out_at'] ?? null;
            $shiftAttendance->break_minutes = (int)($data['break_minutes'] ?? 0);
            $shiftAttendance->status       = $data['status'];
            $shiftAttendance->note         = $data['note'] ?? null;

            // 両方ある場合のみ勤務分再計算（自動休憩控除）
            if ($shiftAttendance->clock_in_at && $shiftAttendance->clock_out_at) {
                [$workMinutes, $autoBreak] = $this->calcWorkMinutesWithAutoBreak(
                    $shiftAttendance->clock_in_at,
                    $shiftAttendance->clock_out_at
                );
                $shiftAttendance->work_minutes = $workMinutes;
                $shiftAttendance->auto_break_minutes = $autoBreak;
            } else {
                $shiftAttendance->work_minutes = 0;
                $shiftAttendance->auto_break_minutes = 0;
            }

            $shiftAttendance->save();

            AttendanceLog::create([
                'shift_id'            => $shiftAttendance->shift_id,
                'shift_attendance_id' => $shiftAttendance->id,
                'user_id'             => $shiftAttendance->user_id,
                'base_id'             => $shiftAttendance->base_id,
                'action'              => 'admin_edit',
                'source'              => 'admin',
                'occurred_at'         => now(),
                'ip_address'          => $request->ip(),
                'user_agent'          => (string)$request->userAgent(),
                'payload'             => [
                    'reason' => $data['reason'],
                    'before' => $before,
                    'after'  => $shiftAttendance->only([
                        'clock_in_at', 'clock_out_at', 'status', 'work_minutes', 'auto_break_minutes', 'break_minutes', 'note'
                    ]),
                ],
                'actor_user_id'       => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('admin.attendances.edit', $shiftAttendance->id)
            ->with('success', '勤怠を修正しました。');
    }

    /**
     * CSV出力（簡易版：拠点×月の全明細）
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensurePermission($request, 'attendance_month', 'view');

        $month  = (string)$request->query('month', Carbon::now()->format('Y-m'));
        $baseId = (int)$request->query('base_id', 0);

        $items = ShiftAttendance::query()
            ->where('base_id', $baseId)
            ->forMonth($month)
            ->with(['user', 'shift'])
            ->orderBy('attendance_date')
            ->orderBy('user_id')
            ->get();

        $filename = "attendances_{$baseId}_{$month}.csv";

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            // Excel対策（UTF-8 BOM）
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                '日付', 'スタッフID', 'スタッフ名', '予定時間', '出勤', '退勤', '自動休憩(分)', '勤務(分)', '勤務(時間)', '状態', 'メモ'
            ]);

            foreach ($items as $a) {
                $name = $a->user ? ($a->user->name ?? trim(($a->user->last_name ?? '').' '.($a->user->first_name ?? ''))) : '';
                $plan = $a->shift ? (($a->shift->start_time ?? '').'〜'.($a->shift->end_time ?? '')) : '';
                fputcsv($out, [
                    optional($a->attendance_date)->format('Y-m-d'),
                    $a->user_id,
                    $name,
                    $plan,
                    optional($a->clock_in_at)->format('Y-m-d H:i:s'),
                    optional($a->clock_out_at)->format('Y-m-d H:i:s'),
                    (int)$a->auto_break_minutes,
                    (int)$a->work_minutes,
                    $a->work_time_label ?? $this->minutesToLabel((int)$a->work_minutes),
                    $a->status,
                    $a->note ?? '',
                ]);
            }

            fclose($out);
        }, $filename);
    }

    /**
     * PDF出力（後で帳票を作り込む：今は仮で 403 返し）
     */
    public function exportPdf(Request $request)
    {
        $this->ensurePermission($request, 'attendance_month', 'view');

        abort(403, 'PDF出力は次のステップで実装します（帳票レイアウト確定後）。');
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

    private function ensureNotClosedOrLocked(ShiftAttendance $attendance): void
    {
        if ($attendance->is_locked) {
            abort(403, 'この月は締め処理済みのため編集できません。');
        }

        $ym = Carbon::parse($attendance->attendance_date)->format('Y-m');
        if (AttendanceClosing::isClosed((int)$attendance->base_id, $ym)) {
            abort(403, 'この月は締め処理済みのため編集できません。');
        }
    }

    private function minutesToLabel(int $minutes): string
    {
        $h = intdiv(max(0, $minutes), 60);
        $m = max(0, $minutes) % 60;
        return sprintf('%d:%02d', $h, $m);
    }

    private function calcWorkMinutesWithAutoBreak($clockInAt, $clockOutAt): array
    {
        $in  = Carbon::parse($clockInAt);
        $out = Carbon::parse($clockOutAt);

        $total = max(0, $in->diffInMinutes($out));

        $autoBreak = 0;
        if ($total >= 8 * 60) {
            $autoBreak = 60;
        } elseif ($total >= 6 * 60) {
            $autoBreak = 45;
        }

        $work = max(0, $total - $autoBreak);

        return [$work, $autoBreak];
    }
}
