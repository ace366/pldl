<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Shift;
use App\Models\ShiftAttendance;
use App\Models\StaffBase;
use App\Models\AttendanceLog;
use App\Models\AttendanceClosing;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffAttendanceController extends Controller
{
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // スタッフの拠点：主担当優先 → 無ければ最初の1件
        $staffBase = StaffBase::query()
            ->where('user_id', $user->id)
            ->with('base')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        $base = $staffBase?->base;

        // 今日のシフト
        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $today)
            ->orderBy('start_time')
            ->first();

        // 実績（shift_attendances）は shift_id で紐付け想定
        $attendance = null;
        if ($shift) {
            $attendance = ShiftAttendance::query()
                ->where('shift_id', $shift->id)
                ->first();

            // 無い場合は作っておく（運用保険）
            if (!$attendance) {
                $attendance = ShiftAttendance::create([
                    'shift_id'           => $shift->id,
                    'base_id'            => $shift->base_id,
                    'user_id'            => $shift->user_id,
                    'attendance_date'    => $shift->shift_date,
                    'status'             => 'scheduled',
                    'break_minutes'      => 0,
                    'auto_break_minutes' => 0,
                    'work_minutes'       => 0,
                    'is_locked'          => false,
                ]);
            }
        }

        $staffName = $user->name ?? trim(($user->last_name ?? '').' '.($user->first_name ?? ''));

        return view('staff.attendance.today', [
            'date'       => $today,
            'base'       => $base,
            'shift'      => $shift,
            'attendance' => $attendance,
            'staffName'  => $staffName ?: 'スタッフ',
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $today)
            ->orderBy('start_time')
            ->first();

        if (!$shift) {
            return back()->with('error', '今日のシフトがありません。管理者に連絡してください。');
        }

        $attendance = ShiftAttendance::query()->where('shift_id', $shift->id)->first();
        if (!$attendance) {
            $attendance = ShiftAttendance::create([
                'shift_id'           => $shift->id,
                'base_id'            => $shift->base_id,
                'user_id'            => $shift->user_id,
                'attendance_date'    => $shift->shift_date,
                'status'             => 'scheduled',
                'break_minutes'      => 0,
                'auto_break_minutes' => 0,
                'work_minutes'       => 0,
                'is_locked'          => false,
            ]);
        }

        $this->ensureNotClosedOrLocked($attendance);

        if ($attendance->clock_in_at) {
            return back()->with('error', '入室はすでに打刻済みです。');
        }

        DB::transaction(function () use ($request, $attendance, $shift, $user) {
            $attendance->clock_in_at = now();
            $attendance->status = 'working';
            $attendance->save();

            AttendanceLog::create([
                'shift_id'            => $attendance->shift_id,
                'shift_attendance_id' => $attendance->id,
                'user_id'             => $attendance->user_id,
                'base_id'             => $attendance->base_id,
                'action'              => 'clock_in',
                'source'              => 'staff',
                'occurred_at'         => now(),
                'ip_address'          => $request->ip(),
                'user_agent'          => (string)$request->userAgent(),
                'payload'             => [
                    'shift_date' => $shift->shift_date,
                ],
                'actor_user_id'       => $user->id,
            ]);
        });

        return back()->with('success', '入室（出勤）を打刻しました。');
    }

    public function clockOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $today)
            ->orderBy('start_time')
            ->first();

        if (!$shift) {
            return back()->with('error', '今日のシフトがありません。管理者に連絡してください。');
        }

        $attendance = ShiftAttendance::query()->where('shift_id', $shift->id)->first();
        if (!$attendance) {
            return back()->with('error', '勤怠レコードが見つかりません。管理者に連絡してください。');
        }

        $this->ensureNotClosedOrLocked($attendance);

        if (!$attendance->clock_in_at) {
            return back()->with('error', '先に入室（出勤）を打刻してください。');
        }
        if ($attendance->clock_out_at) {
            return back()->with('error', '退室はすでに打刻済みです。');
        }

        DB::transaction(function () use ($request, $attendance, $user) {
            $attendance->clock_out_at = now();

            // 勤務分再計算（自動休憩控除）
            [$workMinutes, $autoBreak] = $this->calcWorkMinutesWithAutoBreak($attendance->clock_in_at, $attendance->clock_out_at);
            $attendance->work_minutes = $workMinutes;
            $attendance->auto_break_minutes = $autoBreak;

            $attendance->status = 'completed';
            $attendance->save();

            AttendanceLog::create([
                'shift_id'            => $attendance->shift_id,
                'shift_attendance_id' => $attendance->id,
                'user_id'             => $attendance->user_id,
                'base_id'             => $attendance->base_id,
                'action'              => 'clock_out',
                'source'              => 'staff',
                'occurred_at'         => now(),
                'ip_address'          => $request->ip(),
                'user_agent'          => (string)$request->userAgent(),
                'payload'             => [
                    'work_minutes'       => $workMinutes,
                    'auto_break_minutes' => $autoBreak,
                ],
                'actor_user_id'       => $user->id,
            ]);
        });

        return back()->with('success', '退室（退勤）を打刻しました。');
    }

    public function qr(Request $request)
    {
        $user = $request->user();
        $staffName = $user->name ?? trim(($user->last_name ?? '').' '.($user->first_name ?? ''));

        // ✅ スタッフQRの仕様を統一（MyQrControllerと同じ）
        // スタッフ出勤用：STAFF_ID:{users.id}
        $payload = 'STAFF_ID:' . $user->id;

        return view('staff.attendance.qr', [
            'staffName' => $staffName ?: 'スタッフ',
            'payload'   => $payload,
        ]);
    }

    /**
     * ✅ QR打刻（POST /staff/attendance/qr/clock）
     * - STAFF_ID:{id} のみを正規入力として扱う
     * - 互換：staff:{id} も受ける（過去QRが残っていても動く）
     * - スキャンしたIDが「ログイン中ユーザー」と一致しない場合は拒否（なりすまし防止）
     * - まだ出勤していなければ出勤、出勤済みで退勤前なら退勤、両方済みならエラー
     */
    public function qrClock(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // 入力のキー名は画面実装差で揺れがちなので複数拾う（憶測で固定しない）
        $raw = (string)($request->input('payload')
            ?? $request->input('text')
            ?? $request->input('qr')
            ?? $request->input('code')
            ?? '');

        $raw = trim($raw);

        if ($raw === '') {
            return response()->json([
                'ok'      => false,
                'message' => 'QRの値が受け取れませんでした。',
            ], 422);
        }

        // 正式：STAFF_ID:123
        $staffId = null;
        if (preg_match('/^STAFF_ID:(\d+)$/', $raw, $m)) {
            $staffId = (int)$m[1];
        }
        // 互換：staff:123（旧仕様）
        if ($staffId === null && preg_match('/^staff:(\d+)$/i', $raw, $m)) {
            $staffId = (int)$m[1];
        }

        if (!$staffId) {
            return response()->json([
                'ok'      => false,
                'message' => 'スタッフ用QRではありません。',
            ], 422);
        }

        // ✅ 本人以外のQRを打刻できない（なりすまし防止）
        if ((int)$user->id !== (int)$staffId) {
            return response()->json([
                'ok'      => false,
                'message' => '別スタッフのQRでは打刻できません。',
            ], 403);
        }

        // 今日のシフト
        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $today)
            ->orderBy('start_time')
            ->first();

        if (!$shift) {
            return response()->json([
                'ok'      => false,
                'message' => '今日のシフトがありません。管理者に連絡してください。',
            ], 404);
        }

        // 勤怠レコード確保
        $attendance = ShiftAttendance::query()->where('shift_id', $shift->id)->first();
        if (!$attendance) {
            $attendance = ShiftAttendance::create([
                'shift_id'           => $shift->id,
                'base_id'            => $shift->base_id,
                'user_id'            => $shift->user_id,
                'attendance_date'    => $shift->shift_date,
                'status'             => 'scheduled',
                'break_minutes'      => 0,
                'auto_break_minutes' => 0,
                'work_minutes'       => 0,
                'is_locked'          => false,
            ]);
        }

        $this->ensureNotClosedOrLocked($attendance);

        // ✅ 状態に応じて出勤/退勤を切替
        try {
            $result = DB::transaction(function () use ($request, $attendance, $shift, $user) {
                // まだ出勤していない → clock_in
                if (!$attendance->clock_in_at) {
                    $attendance->clock_in_at = now();
                    $attendance->status = 'working';
                    $attendance->save();

                    AttendanceLog::create([
                        'shift_id'            => $attendance->shift_id,
                        'shift_attendance_id' => $attendance->id,
                        'user_id'             => $attendance->user_id,
                        'base_id'             => $attendance->base_id,
                        'action'              => 'clock_in',
                        'source'              => 'staff_qr',
                        'occurred_at'         => now(),
                        'ip_address'          => $request->ip(),
                        'user_agent'          => (string)$request->userAgent(),
                        'payload'             => [
                            'shift_date' => $shift->shift_date,
                            'via'        => 'qr',
                        ],
                        'actor_user_id'       => $user->id,
                    ]);

                    return ['action' => 'clock_in', 'message' => '入室（出勤）を打刻しました。'];
                }

                // 出勤済みだが退勤前 → clock_out
                if (!$attendance->clock_out_at) {
                    $attendance->clock_out_at = now();

                    [$workMinutes, $autoBreak] = $this->calcWorkMinutesWithAutoBreak(
                        $attendance->clock_in_at,
                        $attendance->clock_out_at
                    );
                    $attendance->work_minutes = $workMinutes;
                    $attendance->auto_break_minutes = $autoBreak;

                    $attendance->status = 'completed';
                    $attendance->save();

                    AttendanceLog::create([
                        'shift_id'            => $attendance->shift_id,
                        'shift_attendance_id' => $attendance->id,
                        'user_id'             => $attendance->user_id,
                        'base_id'             => $attendance->base_id,
                        'action'              => 'clock_out',
                        'source'              => 'staff_qr',
                        'occurred_at'         => now(),
                        'ip_address'          => $request->ip(),
                        'user_agent'          => (string)$request->userAgent(),
                        'payload'             => [
                            'work_minutes'       => $workMinutes,
                            'auto_break_minutes' => $autoBreak,
                            'via'                => 'qr',
                        ],
                        'actor_user_id'       => $user->id,
                    ]);

                    return ['action' => 'clock_out', 'message' => '退室（退勤）を打刻しました。'];
                }

                // 両方済み
                return ['action' => 'none', 'message' => '本日はすでに出勤・退勤が打刻済みです。'];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => '打刻に失敗しました。もう一度お試しください。',
            ], 500);
        }

        $ok = $result['action'] !== 'none';

        return response()->json([
            'ok'      => $ok,
            'action'  => $result['action'],
            'message' => $result['message'],
        ], $ok ? 200 : 409);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $month = (string)$request->query('month', Carbon::now()->format('Y-m'));

        // 勤怠は base_id を持つので、主担当拠点の名前だけ表示（運用で複数拠点対応するなら後で拡張）
        $staffBase = StaffBase::query()
            ->where('user_id', $user->id)
            ->with('base')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        $baseName = $staffBase?->base?->name;

        $items = ShiftAttendance::query()
            ->where('user_id', $user->id)
            ->forMonth($month)
            ->with(['shift'])
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        $totalMinutes = (int)$items->sum('work_minutes');
        $totalLabel = $this->minutesToLabel($totalMinutes);
        $hourlyWage = (int)($user->hourly_wage ?? 0);

        $rows = [];
        foreach ($items as $a) {
            $rows[(int)$a->id] = PayrollCalculator::row($a, $hourlyWage);
        }
        $payrollTotals = PayrollCalculator::totals($items, $hourlyWage);

        $staffName = $user->name ?? trim(($user->last_name ?? '').' '.($user->first_name ?? ''));

        return view('staff.attendance.history', [
            'month'      => $month,
            'items'      => $items,
            'totalLabel' => $totalLabel,
            'baseName'   => $baseName,
            'staffName'  => $staffName ?: 'スタッフ',
            'hourlyWage' => $hourlyWage,
            'payrollRows' => $rows,
            'payrollTotals' => $payrollTotals,
        ]);
    }

    // ---------------------------
    // 内部
    // ---------------------------
    private function ensureNotClosedOrLocked(ShiftAttendance $attendance): void
    {
        if ($attendance->is_locked) {
            abort(403, 'この月は締め処理済みのため打刻できません。');
        }

        $ym = Carbon::parse($attendance->attendance_date)->format('Y-m');
        if (class_exists(\App\Models\AttendanceClosing::class) && AttendanceClosing::isClosed((int)$attendance->base_id, $ym)) {
            abort(403, 'この月は締め処理済みのため打刻できません。');
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
