<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClosing;
use App\Models\Base;
use App\Models\Shift;
use App\Models\ShiftAttendance;
use App\Models\StaffBase;
use App\Services\RolePermissionService;
use App\Support\JapaneseHoliday;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        return view('admin.shifts.create', $this->createViewData($request));
    }

    /**
     * React 作成画面
     */
    public function react(Request $request)
    {
        $this->ensurePermission($request, 'shift_day', 'create');

        return view('admin.shifts.react', $this->createViewData($request));
    }

    private function createViewData(Request $request): array
    {
        $date = (string)$request->query('date', Carbon::today()->toDateString());
        $baseId = (int)$request->query('base_id', 0);

        $bases = Base::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
        if ($baseId <= 0 || !$bases->contains(fn ($b) => (int)$b->id === $baseId)) {
            $baseId = (int)($bases->first()?->id ?? 0);
        }

        return [
            'date'       => $date,
            'baseId'     => $baseId,
            'bases'      => $bases,
            'staffUsers' => $this->staffUsersForBase($baseId),
        ];
    }

    /**
     * 登録（単日/一括）
     */
    public function store(Request $request)
    {
        $this->ensurePermission($request, 'shift_day', 'create');

        $entryMode = (string)$request->input('entry_mode', 'single');

        if ($entryMode === 'bulk') {
            return $this->storeBulk($request);
        }

        return $this->storeSingle($request);
    }

    /**
     * 編集画面
     */
    public function edit(Request $request, Shift $shift)
    {
        $this->ensurePermission($request, 'shift_day', 'update');

        $bases = Base::query()->orderBy('id')->get();

        $baseId = (int)$shift->base_id;

        return view('admin.shifts.edit', [
            'shift'      => $shift->load('attendance'),
            'bases'      => $bases,
            'staffUsers' => $this->staffUsersForBase($baseId),
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

    private function storeSingle(Request $request)
    {
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
            $this->createShiftWithAttendance($data, $request);
        });

        return redirect()
            ->route('admin.shifts.index', [
                'base_id' => $data['base_id'],
                'date'    => $data['shift_date'],
            ])
            ->with('success', 'シフトを作成しました。');
    }

    private function storeBulk(Request $request)
    {
        $data = $request->validate([
            'entry_mode'        => ['required', 'in:bulk'],
            'base_id'           => ['required', 'integer', 'min:1'],
            'user_id'           => ['required', 'integer', 'min:1'],
            'start_time'        => ['required'],
            'end_time'          => ['required'],
            'status'            => ['nullable', 'string', 'max:20'],
            'note'              => ['nullable', 'string'],
            'bulk_start_date'   => ['required', 'date'],
            'bulk_end_date'     => ['required', 'date', 'after_or_equal:bulk_start_date'],
            'bulk_pattern'      => ['required', 'in:daily,weekday'],
            'bulk_weekdays'     => ['nullable', 'array'],
            'bulk_weekdays.*'   => ['integer', 'between:1,5'],
            'confirm_overwrite' => ['nullable', 'boolean'],
        ]);

        $data['status'] = $data['status'] ?? 'scheduled';
        $data['confirm_overwrite'] = $request->boolean('confirm_overwrite');
        $data['bulk_weekdays'] = collect($data['bulk_weekdays'] ?? [])
            ->map(fn ($day) => (int)$day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($data['bulk_pattern'] === 'weekday' && empty($data['bulk_weekdays'])) {
            return back()
                ->withErrors(['bulk_weekdays' => '曜日指定登録では、少なくとも1つ曜日を選択してください。'])
                ->withInput();
        }

        $rangeStart = Carbon::parse($data['bulk_start_date'])->startOfDay();
        $rangeEnd = Carbon::parse($data['bulk_end_date'])->startOfDay();
        if ($rangeStart->diffInDays($rangeEnd) > 366) {
            return back()
                ->withErrors(['bulk_end_date' => '一括登録の期間は1年以内で指定してください。'])
                ->withInput();
        }

        $bulkTargets = $this->collectBulkTargetDates($data);
        if (empty($bulkTargets['target_dates'])) {
            return back()
                ->withErrors(['bulk_start_date' => '登録対象日がありません。期間・曜日・除外条件を確認してください。'])
                ->withInput()
                ->with('shift_bulk_preview', $this->buildBulkPreview($data, $bulkTargets, [
                    'duplicates' => [],
                    'blocked' => [],
                ]));
        }

        $targetDateStrings = array_map(
            fn (Carbon $date) => $date->toDateString(),
            $bulkTargets['target_dates']
        );

        $existingGroups = Shift::query()
            ->where('base_id', $data['base_id'])
            ->where('user_id', $data['user_id'])
            ->whereIn('shift_date', $targetDateStrings)
            ->with('attendance')
            ->orderBy('shift_date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Shift $shift) => $shift->shift_date->format('Y-m-d'));

        $analysis = $this->analyzeBulkExistingShifts($existingGroups);
        $preview = $this->buildBulkPreview($data, $bulkTargets, $analysis);

        if (!empty($analysis['blocked'])) {
            return back()
                ->withErrors(['confirm_overwrite' => '上書きできない既存シフトがあります。内容を確認し、日別編集で調整してください。'])
                ->withInput()
                ->with('shift_bulk_preview', $preview);
        }

        if (!$data['confirm_overwrite'] && !empty($analysis['duplicates'])) {
            return back()
                ->withErrors(['confirm_overwrite' => '既存シフトがある日があります。確認のうえ「上書きして登録する」にチェックして再実行してください。'])
                ->withInput()
                ->with('shift_bulk_preview', $preview);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($request, $data, $targetDateStrings, $existingGroups, &$created, &$updated) {
            foreach ($targetDateStrings as $dateString) {
                /** @var \Illuminate\Support\Collection<int,\App\Models\Shift> $existingForDate */
                $existingForDate = $existingGroups->get($dateString, collect());
                $payload = $this->shiftPayloadForDate($data, $request, $dateString);

                if ($existingForDate->count() === 1) {
                    $this->overwriteShiftWithAttendance($existingForDate->first(), $payload, $request);
                    $updated++;
                    continue;
                }

                $this->createShiftWithAttendance($payload, $request);
                $created++;
            }
        });

        $firstTargetDate = $targetDateStrings[0] ?? $data['bulk_start_date'];
        $excludedCount = count($bulkTargets['excluded_weekends'])
            + count($bulkTargets['excluded_holidays'])
            + count($bulkTargets['excluded_by_pattern']);

        return redirect()
            ->route('admin.shifts.index', [
                'base_id' => $data['base_id'],
                'date'    => $firstTargetDate,
            ])
            ->with(
                'success',
                sprintf(
                    '一括登録を完了しました。新規 %d 件 / 上書き %d 件 / 除外 %d 日',
                    $created,
                    $updated,
                    $excludedCount
                )
            );
    }

    private function createShiftWithAttendance(array $payload, Request $request): Shift
    {
        $shift = Shift::create([
            'base_id'    => $payload['base_id'],
            'user_id'    => $payload['user_id'],
            'shift_date' => $payload['shift_date'],
            'start_time' => $payload['start_time'],
            'end_time'   => $payload['end_time'],
            'status'     => $payload['status'],
            'note'       => $payload['note'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        ShiftAttendance::create([
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

        return $shift;
    }

    private function overwriteShiftWithAttendance(Shift $shift, array $payload, Request $request): void
    {
        $shift->fill([
            'base_id'    => $payload['base_id'],
            'user_id'    => $payload['user_id'],
            'shift_date' => $payload['shift_date'],
            'start_time' => $payload['start_time'],
            'end_time'   => $payload['end_time'],
            'status'     => $payload['status'],
            'note'       => $payload['note'] ?? null,
            'updated_by' => $request->user()->id,
        ])->save();

        $attendance = $shift->attendance;
        if (!$attendance) {
            ShiftAttendance::create([
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
            return;
        }

        $attendance->fill([
            'base_id'         => $shift->base_id,
            'user_id'         => $shift->user_id,
            'attendance_date' => $shift->shift_date,
        ])->save();
    }

    private function shiftPayloadForDate(array $data, Request $request, string $dateString): array
    {
        return [
            'base_id'    => $data['base_id'],
            'user_id'    => $data['user_id'],
            'shift_date' => $dateString,
            'start_time' => $data['start_time'],
            'end_time'   => $data['end_time'],
            'status'     => $data['status'] ?? 'scheduled',
            'note'       => $data['note'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];
    }

    private function collectBulkTargetDates(array $data): array
    {
        $start = Carbon::parse($data['bulk_start_date'])->startOfDay();
        $end = Carbon::parse($data['bulk_end_date'])->startOfDay();
        $selectedWeekdays = $data['bulk_pattern'] === 'weekday'
            ? array_flip($data['bulk_weekdays'])
            : [];

        $targetDates = [];
        $excludedWeekends = [];
        $excludedHolidays = [];
        $excludedByPattern = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekend()) {
                $excludedWeekends[] = $date->toDateString();
                continue;
            }

            $holidayName = JapaneseHoliday::name($date);
            if ($holidayName !== null) {
                $excludedHolidays[] = [
                    'date' => $date->toDateString(),
                    'name' => $holidayName,
                ];
                continue;
            }

            if (
                $data['bulk_pattern'] === 'weekday'
                && !isset($selectedWeekdays[$date->dayOfWeekIso])
            ) {
                $excludedByPattern[] = $date->toDateString();
                continue;
            }

            $targetDates[] = $date->copy();
        }

        return [
            'target_dates'        => $targetDates,
            'excluded_weekends'   => $excludedWeekends,
            'excluded_holidays'   => $excludedHolidays,
            'excluded_by_pattern' => $excludedByPattern,
        ];
    }

    private function analyzeBulkExistingShifts(Collection $existingGroups): array
    {
        $duplicates = [];
        $blocked = [];

        foreach ($existingGroups as $date => $items) {
            if ($items->count() > 1) {
                $duplicates[] = $date;
                $blocked[] = [
                    'date' => $date,
                    'reason' => '同じ日付に既存シフトが複数件あるため、一括上書きできません。',
                ];
                continue;
            }

            /** @var \App\Models\Shift $shift */
            $shift = $items->first();
            $duplicates[] = $date;

            $reason = $this->bulkOverwriteBlockReason($shift);
            if ($reason !== null) {
                $blocked[] = [
                    'date' => $date,
                    'reason' => $reason,
                ];
            }
        }

        sort($duplicates);

        return [
            'duplicates' => $duplicates,
            'blocked' => $blocked,
        ];
    }

    private function bulkOverwriteBlockReason(Shift $shift): ?string
    {
        $attendance = $shift->attendance;
        if (!$attendance) {
            return null;
        }

        $yearMonth = Carbon::parse($attendance->attendance_date ?? $shift->shift_date)->format('Y-m');
        if (AttendanceClosing::isClosed((int)$attendance->base_id, $yearMonth)) {
            return '勤怠締め済みのため上書きできません。';
        }

        if ((bool)$attendance->is_locked) {
            return '勤怠がロックされているため上書きできません。';
        }

        if ($attendance->clock_in_at || $attendance->clock_out_at) {
            return '打刻済み勤怠があるため上書きできません。';
        }

        if ((string)($attendance->status ?? 'scheduled') !== 'scheduled') {
            return '勤怠ステータスが予定以外のため上書きできません。';
        }

        return null;
    }

    private function buildBulkPreview(array $data, array $bulkTargets, array $analysis): array
    {
        $targetDateStrings = array_map(
            fn (Carbon $date) => $date->toDateString(),
            $bulkTargets['target_dates']
        );

        $duplicateDates = $analysis['duplicates'] ?? [];
        $newDates = array_values(array_diff($targetDateStrings, $duplicateDates));

        return [
            'entry_mode' => 'bulk',
            'pattern' => $data['bulk_pattern'],
            'target_dates' => $targetDateStrings,
            'new_dates' => $newDates,
            'duplicate_dates' => $duplicateDates,
            'blocked' => $analysis['blocked'] ?? [],
            'excluded_weekends' => $bulkTargets['excluded_weekends'],
            'excluded_holidays' => $bulkTargets['excluded_holidays'],
            'excluded_by_pattern' => $bulkTargets['excluded_by_pattern'],
        ];
    }

    private function staffUsersForBase(int $baseId): Collection
    {
        if ($baseId <= 0) {
            return collect();
        }

        return StaffBase::query()
            ->where('base_id', $baseId)
            ->with('user')
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn ($sb) => $sb->user)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function ensurePermission(Request $request, string $feature, string $action): void
    {
        $user = $request->user();
        if (!RolePermissionService::canUser($user, $feature, $action)) {
            abort(403);
        }
    }
}
