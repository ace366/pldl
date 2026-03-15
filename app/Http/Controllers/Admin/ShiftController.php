<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Shift;
use App\Models\ShiftAttendance;
use App\Models\StaffBase;
use App\Services\RolePermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /**
     * 日別一覧
     * /admin/shifts?base_id=...&date=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        $this->ensurePermission($request, 'shift_day', 'view');

        $date = (string)$request->query('date', Carbon::today()->toDateString());
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($baseId <= 0 || !$bases->contains(fn ($b) => (int)$b->id === $baseId)) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $shifts = collect();
        if ($baseId > 0) {
            $shifts = Shift::query()
                ->where('base_id', $baseId)
                ->whereDate('shift_date', $date)
                ->with(['user', 'attendance'])
                ->orderBy('start_time')
                ->orderBy('id')
                ->get();
        }

        return view('admin.shifts.index', [
            'date'  => $date,
            'baseId'=> $baseId,
            'bases' => $bases,
            'shifts'=> $shifts,
        ]);
    }

    /**
     * 月表示（まずは「日付リスト」形式でOK）
     * /admin/shifts/month?base_id=...&month=YYYY-MM
     */
    public function month(Request $request)
    {
        $this->ensurePermission($request, 'shift_month', 'view');

        $month = (string)$request->query('month', Carbon::now()->format('Y-m'));
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
        if ($baseId <= 0 || !$bases->contains(fn ($b) => (int)$b->id === $baseId)) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        $days = [];
        if ($baseId > 0) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end   = (clone $start)->endOfMonth();

            // 日別の件数だけ出す（軽い）
            $counts = Shift::query()
                ->where('base_id', $baseId)
                ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw('shift_date, COUNT(*) as cnt')
                ->groupBy('shift_date')
                ->pluck('cnt', 'shift_date')
                ->all();

            $cur = $start->copy();
            while ($cur <= $end) {
                $d = $cur->toDateString();
                $days[] = [
                    'date' => $d,
                    'count'=> (int)($counts[$d] ?? 0),
                ];
                $cur->addDay();
            }
        }

        return view('admin.shifts.month', [
            'month' => $month,
            'baseId'=> $baseId,
            'bases' => $bases,
            'days'  => $days,
        ]);
    }

    /**
     * 作成画面
     * /admin/shifts/create?base_id=...&date=YYYY-MM-DD
     */
    public function create(Request $request)
    {
        $this->ensurePermission($request, 'shift_day', 'create');

        $date = (string)$request->query('date', Carbon::today()->toDateString());
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
        if ($baseId <= 0 || !$bases->contains(fn ($b) => (int)$b->id === $baseId)) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        // 拠点に所属しているスタッフ一覧
        $staffUsers = StaffBase::query()
            ->where('base_id', $baseId)
            ->with('user')
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn($sb) => $sb->user)
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.shifts.create', [
            'date'       => $date,
            'baseId'     => $baseId,
            'bases'      => $bases,
            'staffUsers' => $staffUsers,
        ]);
    }

    /**
     * 登録（同時に shift_attendances も作る）
     */
    public function store(Request $request)
    {
        $this->ensurePermission($request, 'shift_day', 'create');

        $data = $request->validate([
            'base_id'    => ['required', 'integer', 'min:1'],
            'user_id'    => ['required', 'integer', 'min:1'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time'   => ['required'],
            'status'     => ['nullable', 'string', 'max:20'],
            'note'       => ['nullable', 'string'],
        ]);

        $data['status'] = $data['status'] ?? 'scheduled';

        DB::transaction(function () use ($data, $request) {
            $shift = Shift::create([
                'base_id'    => $data['base_id'],
                'user_id'    => $data['user_id'],
                'shift_date' => $data['shift_date'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'status'     => $data['status'],
                'note'       => $data['note'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            // 空の実績を作成（運用がラクになる）
            ShiftAttendance::create([
                'shift_id'         => $shift->id,
                'base_id'          => $shift->base_id,
                'user_id'          => $shift->user_id,
                'attendance_date'  => $shift->shift_date,
                'status'           => 'scheduled',
                'break_minutes'    => 0,
                'auto_break_minutes' => 0,
                'work_minutes'     => 0,
                'is_locked'        => false,
            ]);
        });

        return redirect()
            ->route('admin.shifts.index', [
                'base_id' => $data['base_id'],
                'date'    => $data['shift_date'],
            ])
            ->with('success', 'シフトを作成しました。');
    }

    /**
     * 編集画面
     */
    public function edit(Request $request, Shift $shift)
    {
        $this->ensurePermission($request, 'shift_day', 'update');

        $bases = Base::query()->orderBy('id')->get();

        $baseId = (int)$shift->base_id;

        $staffUsers = StaffBase::query()
            ->where('base_id', $baseId)
            ->with('user')
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn($sb) => $sb->user)
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.shifts.edit', [
            'shift'      => $shift->load('attendance'),
            'bases'      => $bases,
            'staffUsers' => $staffUsers,
        ]);
    }

    /**
     * 更新（attendance の user/base/date も同期）
     */
    public function update(Request $request, Shift $shift)
    {
        $this->ensurePermission($request, 'shift_day', 'update');

        $data = $request->validate([
            'base_id'    => ['required', 'integer', 'min:1'],
            'user_id'    => ['required', 'integer', 'min:1'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time'   => ['required'],
            'status'     => ['required', 'string', 'max:20'],
            'note'       => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request, $shift) {
            $shift->fill([
                'base_id'    => $data['base_id'],
                'user_id'    => $data['user_id'],
                'shift_date' => $data['shift_date'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'status'     => $data['status'],
                'note'       => $data['note'] ?? null,
                'updated_by' => $request->user()->id,
            ])->save();

            // 実績も同期
            $attendance = $shift->attendance;
            if ($attendance) {
                $attendance->fill([
                    'base_id'         => $shift->base_id,
                    'user_id'         => $shift->user_id,
                    'attendance_date' => $shift->shift_date,
                ])->save();
            }
        });

        return redirect()
            ->route('admin.shifts.index', [
                'base_id' => $shift->base_id,
                'date'    => $shift->shift_date->format('Y-m-d'),
            ])
            ->with('success', 'シフトを更新しました。');
    }

    /**
     * 削除（soft delete）
     */
    public function destroy(Request $request, Shift $shift)
    {
        $this->ensurePermission($request, 'shift_day', 'delete');

        $baseId = (int)$shift->base_id;
        $date   = $shift->shift_date ? $shift->shift_date->format('Y-m-d') : Carbon::today()->toDateString();

        $shift->delete();

        return redirect()
            ->route('admin.shifts.index', ['base_id' => $baseId, 'date' => $date])
            ->with('success', 'シフトを削除しました。');
    }

    // ---------------------------------------------------------------------
    // 内部: admin判定（既存ミドルウェアが無い環境でも安全に）
    // ---------------------------------------------------------------------
    private function ensurePermission(Request $request, string $feature, string $action): void
    {
        $user = $request->user();
        if (!RolePermissionService::canUser($user, $feature, $action)) {
            abort(403);
        }
    }
}
