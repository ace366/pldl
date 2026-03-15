<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\ChildAttendanceIntent;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TodayAttendanceController extends Controller
{
    private function resolveStateForChild(
        int $childId,
        \Illuminate\Support\Collection $inIds,
        \Illuminate\Support\Collection $outIds,
        ?ChildAttendanceIntent $intent
    ): string {
        $hasIn = $inIds->contains($childId);
        $hasOut = $outIds->contains($childId);
        $manualStatus = (string)($intent?->manual_status ?? '');
        $pickupConfirmed = (bool)($intent?->pickup_confirmed ?? false);

        if ($hasOut) {
            return 'checked_out';
        }
        if ($manualStatus === 'arrived') {
            return 'attending';
        }
        if ($manualStatus === 'not_arrived') {
            return 'absent';
        }
        if ($hasIn) {
            return 'attending';
        }
        if ($pickupConfirmed) {
            return 'pickup';
        }

        return 'registered';
    }

    private function resolveParticipantIds(
        \Illuminate\Support\Collection $inIds,
        \Illuminate\Support\Collection $outIds,
        \Illuminate\Support\Collection $intentsByChild
    ): \Illuminate\Support\Collection {
        $intentIds = $intentsByChild
            ->keys();

        return $inIds
            ->merge($outIds)
            ->merge($intentIds)
            ->unique()
            ->map(fn ($id) => (int)$id)
            ->filter(fn ($id) => $id > 0)
            ->values();
    }

    public function index(Request $request)
    {
        $dateParam = (string)$request->query('date', '');
        try {
            $today = $dateParam !== '' ? \Carbon\Carbon::parse($dateParam)->toDateString() : now()->toDateString();
        } catch (\Exception $e) {
            $today = now()->toDateString();
        }

        $inIds = Attendance::query()
            ->whereDate('attended_at', $today)
            ->where(function ($q) {
                $q->whereNull('attendance_type')
                    ->orWhere('attendance_type', 'in');
            })
            ->pluck('child_id')
            ->all();

        $outIds = Attendance::query()
            ->whereDate('attended_at', $today)
            ->where('attendance_type', 'out')
            ->pluck('child_id')
            ->all();

        $intentsByChild = ChildAttendanceIntent::query()
            ->whereDate('date', $today)
            ->orderByDesc('id')
            ->get()
            ->groupBy('child_id')
            ->map(fn ($rows) => $rows->first());

        $participantIds = $this->resolveParticipantIds(
            collect($inIds),
            collect($outIds),
            $intentsByChild
        )->all();

        $adminReadKey = null;
        if (Schema::hasTable('family_message_admin_reads')) {
            if (Schema::hasColumn('family_message_admin_reads', 'family_message_id')) {
                $adminReadKey = 'family_message_id';
            } elseif (Schema::hasColumn('family_message_admin_reads', 'message_id')) {
                $adminReadKey = 'message_id';
            }
        }

        $hasDeletedAt = Schema::hasColumn('family_messages', 'deleted_at');

        $children = Child::query()
            ->with([
                'baseMaster',
                'school',
                'guardians' => function ($q) {
                    $q->select(
                        'guardians.id',
                        'guardians.last_name',
                        'guardians.first_name',
                        'guardians.email',
                        'guardians.phone'
                    )->withPivot(['relationship', 'relation']);
                },
            ])
            ->select('children.*')
            ->selectSub(function ($sub) use ($adminReadKey, $hasDeletedAt) {
                $sub->from('family_messages as fm')
                    ->whereColumn('fm.child_id', 'children.id');

                if ($hasDeletedAt) {
                    $sub->whereNull('fm.deleted_at');
                }

                if (Schema::hasColumn('family_messages', 'sender_type')) {
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
            ->when(!empty($participantIds), fn ($q) => $q->whereIn('children.id', $participantIds))
            ->when(empty($participantIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('children.grade')
            ->orderBy('children.last_name')
            ->orderBy('children.first_name')
            ->get();

        $inIdSet = collect($inIds)->map(fn ($id) => (int)$id)->unique()->values();
        $outIdSet = collect($outIds)->map(fn ($id) => (int)$id)->unique()->values();

        $children = $children->map(function ($c) use ($intentsByChild, $inIdSet, $outIdSet) {
            $intent = $intentsByChild->get((int)$c->id);
            $state = $this->resolveStateForChild((int)$c->id, $inIdSet, $outIdSet, $intent);

            $c->today_state = $state;
            $c->today_intent_id = $intent?->id;
            $c->today_pickup_required = (bool)($intent?->pickup_required ?? false);
            $c->today_pickup_confirmed = (bool)($intent?->pickup_confirmed ?? false);
            $c->today_pickup_confirmed_at = $intent?->pickup_confirmed_at
                ? $intent->pickup_confirmed_at->format('H:i')
                : null;
            $c->today_manual_status = $intent?->manual_status;

            return $c;
        });

        $grouped = $children->groupBy(function ($c) {
            return $c->school?->name ?? '未設定';
        });

        return view('admin.children.today', [
            'children' => $children,
            'grouped' => $grouped,
            'today' => $today,
        ]);
    }

    public function react(Request $request)
    {
        $dateParam = (string)$request->query('date', '');
        try {
            $today = $dateParam !== '' ? \Carbon\Carbon::parse($dateParam)->toDateString() : now()->toDateString();
        } catch (\Exception $e) {
            $today = now()->toDateString();
        }

        return view('admin.children.today_react', [
            'date' => $today,
            'apiSummaryUrl' => route('admin.children.today.summary'),
            'apiTogglePickupUrl' => route('admin.attendance_intents.api.toggle_pickup'),
            'apiToggleManualUrl' => route('admin.attendance_intents.api.toggle_manual'),
            'apiCheckoutUrl' => route('admin.children.checkout', ['child' => 0]),
        ]);
    }

    public function summary(Request $request)
    {
        $dateParam = (string)$request->query('date', '');
        try {
            $date = $dateParam !== '' ? \Carbon\Carbon::parse($dateParam)->toDateString() : now()->toDateString();
        } catch (\Exception $e) {
            $date = now()->toDateString();
        }

        $schools = School::query()->orderBy('name')->get();

        $intentsAll = ChildAttendanceIntent::query()
            ->with(['child.school'])
            ->whereDate('date', $date)
            ->get();
        $intentsByChild = $intentsAll
            ->sortByDesc('id')
            ->groupBy('child_id')
            ->map(fn ($rows) => $rows->first());

        $inIds = Attendance::query()
            ->whereDate('attended_at', $date)
            ->where(function ($q) {
                $q->whereNull('attendance_type')
                    ->orWhere('attendance_type', 'in');
            })
            ->pluck('child_id')
            ->unique();

        $outIds = Attendance::query()
            ->whereDate('attended_at', $date)
            ->where('attendance_type', 'out')
            ->pluck('child_id')
            ->unique();

        $payload = ['date' => $date, 'schools' => []];

        $participantIds = $this->resolveParticipantIds($inIds, $outIds, $intentsByChild);

        if ($participantIds->isEmpty()) {
            return response()->json($payload);
        }

        $childrenAll = Child::query()
            ->with(['school'])
            ->whereIn('id', $participantIds->all())
            ->orderBy('grade')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $schoolNames = $schools->keyBy('id')->map(fn ($s) => (string)$s->name);
        $inIdSet = $inIds->map(fn ($id) => (int)$id)->unique()->values();
        $outIdSet = $outIds->map(fn ($id) => (int)$id)->unique()->values();

        $grouped = $childrenAll->groupBy(fn ($c) => $c->school?->id);

        foreach ($grouped as $schoolId => $childrenInSchool) {
            $children = $childrenInSchool->map(function ($child) use ($intentsByChild, $inIdSet, $outIdSet) {
                $intent = $intentsByChild->get((int)$child->id);
                $state = $this->resolveStateForChild((int)$child->id, $inIdSet, $outIdSet, $intent);
                $isAttending = $state === 'attending';
                $isCheckedOut = $state === 'checked_out';

                return [
                    'intent_id'           => $intent?->id ? (int)$intent->id : null,
                    'child_id'            => (int)$child->id,
                    'child_name'          => (string)($child->full_name ?? ''),
                    'pickup_required'     => (bool)($intent?->pickup_required ?? false),
                    'pickup_confirmed'    => (bool)($intent?->pickup_confirmed ?? false),
                    'pickup_confirmed_at' => $intent?->pickup_confirmed_at
                        ? $intent->pickup_confirmed_at->toISOString()
                        : null,
                    'arrived'             => $isAttending,
                    'checked_out'         => $isCheckedOut,
                    'state'               => $state,
                    'manual_status'       => $intent?->manual_status,
                    'manual_updated_at'   => $intent?->manual_updated_at
                        ? $intent->manual_updated_at->toISOString()
                        : null,
                ];
            })->values();

            $pickupCount = $children->where('pickup_required', true)->count();
            $cars = (int) ceil($pickupCount / 4);

            $payload['schools'][] = [
                'school_id'      => $schoolId !== '' && $schoolId !== null ? (int)$schoolId : null,
                'school_name'    => $schoolId !== '' && $schoolId !== null
                    ? ($schoolNames->get((int)$schoolId) ?? (string)($childrenInSchool->first()?->school?->name ?? '学校未設定'))
                    : '学校未設定',
                'total'          => (int)$children->count(),
                'pickup'         => (int)$pickupCount,
                'cars'           => (int)$cars,
                'attending'      => (int)$children->where('state', 'attending')->count(),
                'pickup_active'  => (int)$children->where('state', 'pickup')->count(),
                'checked_out'    => (int)$children->where('state', 'checked_out')->count(),
                'children'       => $children,
            ];
        }

        return response()->json($payload);
    }

    public function checkout(Request $request, Child $child)
    {
        $dateParam = (string)$request->input('date', '');
        try {
            $targetDate = $dateParam !== '' ? \Carbon\Carbon::parse($dateParam)->toDateString() : now()->toDateString();
        } catch (\Exception $e) {
            $targetDate = now()->toDateString();
        }

        $attendedAt = \Carbon\Carbon::parse($targetDate)->setTimeFrom(now());

        Attendance::create([
            'child_id' => $child->id,
            'scanned_by' => Auth::id(),
            'attendance_type' => 'out',
            'attended_at' => $attendedAt,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.children.today', ['date' => $targetDate])
            ->with('success', '帰宅にしました。');
    }
}
