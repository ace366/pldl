<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ChildAttendanceIntent;
use App\Models\School;
use Illuminate\Http\Request;

class AttendanceIntentApiController extends Controller
{
    /**
     * React用：学校別のサマリ＋児童一覧
     * GET ?date=YYYY-MM-DD
     */
    public function summary(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $schools = School::where('is_active', 1)->orderBy('name')->get();

        $intentsAll = ChildAttendanceIntent::query()
            ->with(['child.school'])
            ->whereDate('date', $date)
            ->get();

        $intentsBySchool = $intentsAll->groupBy(function ($intent) {
            return $intent->child?->school_id; // nullあり
        });

        $attendedChildIds = Attendance::whereDate('attended_at', $date)
            ->pluck('child_id')
            ->unique();

        $payload = [
            'date'    => $date,
            'schools' => [],
        ];

        foreach ($schools as $school) {
            $list = $intentsBySchool->get($school->id, collect());

            $pickupCount = $list->where('pickup_required', 1)->count();
            $cars = (int) ceil($pickupCount / 4);

            $children = $list->map(function ($intent) use ($attendedChildIds) {
                $autoArrived = $attendedChildIds->contains((int)$intent->child_id);

                // 手動優先
                if ($intent->manual_status === 'arrived') {
                    $arrived = true;
                } elseif ($intent->manual_status === 'not_arrived') {
                    $arrived = false;
                } else {
                    $arrived = $autoArrived;
                }

                return [
                    'intent_id'           => (int)$intent->id,
                    'child_id'            => (int)$intent->child_id,
                    'child_name'          => (string)($intent->child?->full_name ?? ''),
                    'child_name_kana'     => trim((string)($intent->child?->last_name_kana ?? '').' '.(string)($intent->child?->first_name_kana ?? '')),
                    'pickup_required'     => (bool)$intent->pickup_required,
                    'pickup_confirmed'    => (bool)$intent->pickup_confirmed,
                    'pickup_confirmed_at' => $intent->pickup_confirmed_at
                        ? $intent->pickup_confirmed_at->toISOString()
                        : null,

                    'arrived'             => (bool)$arrived,
                    'manual_status'       => $intent->manual_status, // arrived / not_arrived / null
                    'manual_updated_at'   => $intent->manual_updated_at
                        ? $intent->manual_updated_at->toISOString()
                        : null,
                ];
            })->values();

            $payload['schools'][] = [
                'school_id'   => (int)$school->id,
                'school_name' => (string)$school->name,
                'total'       => (int)$list->count(),
                'pickup'      => (int)$pickupCount,
                'cars'        => (int)$cars,
                'children'    => $children,
            ];
        }

        // 学校未設定
        $nullList = $intentsBySchool->get(null, collect());
        if ($nullList->isNotEmpty()) {
            $pickupCount = $nullList->where('pickup_required', 1)->count();
            $cars = (int) ceil($pickupCount / 4);

            $children = $nullList->map(function ($intent) use ($attendedChildIds) {
                $autoArrived = $attendedChildIds->contains((int)$intent->child_id);

                if ($intent->manual_status === 'arrived') {
                    $arrived = true;
                } elseif ($intent->manual_status === 'not_arrived') {
                    $arrived = false;
                } else {
                    $arrived = $autoArrived;
                }

                return [
                    'intent_id'           => (int)$intent->id,
                    'child_id'            => (int)$intent->child_id,
                    'child_name'          => (string)($intent->child?->full_name ?? ''),
                    'child_name_kana'     => trim((string)($intent->child?->last_name_kana ?? '').' '.(string)($intent->child?->first_name_kana ?? '')),
                    'pickup_required'     => (bool)$intent->pickup_required,
                    'pickup_confirmed'    => (bool)$intent->pickup_confirmed,
                    'pickup_confirmed_at' => $intent->pickup_confirmed_at
                        ? $intent->pickup_confirmed_at->toISOString()
                        : null,

                    'arrived'             => (bool)$arrived,
                    'manual_status'       => $intent->manual_status,
                    'manual_updated_at'   => $intent->manual_updated_at
                        ? $intent->manual_updated_at->toISOString()
                        : null,
                ];
            })->values();

            $payload['schools'][] = [
                'school_id'   => null,
                'school_name' => '学校未設定',
                'total'       => (int)$nullList->count(),
                'pickup'      => (int)$pickupCount,
                'cars'        => (int)$cars,
                'children'    => $children,
            ];
        }

        return response()->json($payload);
    }

    /**
     * 送迎：乗車確認トグル
     */
    public function togglePickup(Request $request)
    {
        $validated = $request->validate([
            'intent_id' => ['required', 'integer', 'exists:child_attendance_intents,id'],
        ]);

        $intent = ChildAttendanceIntent::findOrFail((int)$validated['intent_id']);

        $intent->pickup_confirmed = !$intent->pickup_confirmed;
        $intent->pickup_confirmed_at = $intent->pickup_confirmed ? now() : null;
        $intent->save();

        return response()->json([
            'ok'                 => true,
            'intent_id'           => (int)$intent->id,
            'pickup_confirmed'    => (bool)$intent->pickup_confirmed,
            'pickup_confirmed_at' => $intent->pickup_confirmed_at
                ? $intent->pickup_confirmed_at->toISOString()
                : null,
        ]);
    }

    /**
     * 手動ステータス：arrived / not_arrived / auto(null)
     */
    public function toggleManual(Request $request)
    {
        $validated = $request->validate([
            'intent_id'     => ['required', 'integer', 'exists:child_attendance_intents,id'],
            'manual_status' => ['required', 'in:auto,arrived,not_arrived'],
        ]);

        $intent = ChildAttendanceIntent::findOrFail((int)$validated['intent_id']);

        if ($validated['manual_status'] === 'auto') {
            $intent->manual_status = null;
        } else {
            $intent->manual_status = $validated['manual_status'];
        }

        $intent->manual_updated_at = now();
        $intent->save();

        return response()->json([
            'ok'               => true,
            'intent_id'         => (int)$intent->id,
            'manual_status'     => $intent->manual_status, // null or arrived/not_arrived
            'manual_updated_at' => $intent->manual_updated_at
                ? $intent->manual_updated_at->toISOString()
                : null,
        ]);
    }
}
