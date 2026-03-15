<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChildAttendanceIntent;
use App\Models\Attendance;
use App\Models\School;
use Illuminate\Http\Request;

class AttendanceIntentController extends Controller
{
    private function persistManualStatus(ChildAttendanceIntent $intent, ?string $manualStatus): void
    {
        $intent->manual_status = $manualStatus;
        $intent->manual_updated_at = now();
        $intent->save();
    }

    private function manualStatusErrorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }

    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        // 学校（DB）
        $schools = School::where('is_active', 1)->orderBy('name')->get();

        // 参加予定（child を必ず読む：学校は child 側で判断）
        $intentsAll = ChildAttendanceIntent::query()
            ->with(['child.school'])          // ← school は child のリレーションから
            ->whereDate('date', $date)      // ← date型/日時型どちらでも吸収
            ->get();

        // child.school_id で学校別にグループ化（intent.base_id は使わない）
        $intentsBySchool = $intentsAll->groupBy(function ($intent) {
            return $intent->child?->school_id;   // NULL もあり得る
        });

        // 当日の実出席（attended_at が確定）
        $attendedChildIds = Attendance::whereDate('attended_at', $date)
            ->pluck('child_id')
            ->unique();

        $summary = [];

        // まずDB学校分を作る
        foreach ($schools as $school) {
            $list = $intentsBySchool->get($school->id, collect());

            $total = $list->count();

            // ✅ 送迎人数：pickup_required = 1 の人数
            $pickupCount = $list->where('pickup_required', 1)->count();

            // ✅ 必要車両：1台4人（ルールは後で変更OK）
            $cars = (int) ceil($pickupCount / 4);

            // ✅ 未到着：attendance に child_id が無い
            $notArrived = $list->filter(function ($intent) use ($attendedChildIds) {
                return !$attendedChildIds->contains((int)$intent->child_id);
            });

            $summary[$school->id] = [
                'school'     => $school,
                'total'      => $total,
                'pickup'     => $pickupCount,
                'cars'       => $cars,
                'children'   => $list,
                'notArrived' => $notArrived,
            ];
        }

        // school未設定の児童がいる場合も表示（学校未設定枠）
        $nullList = $intentsBySchool->get(null, collect());
        if ($nullList->isNotEmpty()) {
            $total = $nullList->count();
            $pickupCount = $nullList->where('pickup_required', 1)->count();
            $cars = (int) ceil($pickupCount / 4);

            $notArrived = $nullList->filter(function ($intent) use ($attendedChildIds) {
                return !$attendedChildIds->contains((int)$intent->child_id);
            });

            $summary['null'] = [
                'school'     => (object)['id' => null, 'name' => '学校未設定'],
                'total'      => $total,
                'pickup'     => $pickupCount,
                'cars'       => $cars,
                'children'   => $nullList,
                'notArrived' => $notArrived,
            ];
        }

        return view('admin.attendance_intents.index', [
            'date'    => $date,
            'schools' => $schools,
            'summary' => $summary,
        ]);
    }

    /**
     * ✅ 手動で「出席済 / 未到着 / 自動」に切り替える
     */
    public function toggleStatus(Request $request)
    {
        $validated = $request->validate([
            'intent_id'     => ['required', 'integer', 'exists:child_attendance_intents,id'],
            'manual_status' => ['required', 'in:auto,arrived,not_arrived'],
        ]);

        $intent = ChildAttendanceIntent::findOrFail((int)$validated['intent_id']);

        $this->persistManualStatus(
            $intent,
            $validated['manual_status'] === 'auto' ? null : $validated['manual_status']
        );

        return back()->with('success', '状態を更新しました。');
    }

    public function markAbsent(Request $request)
    {
        $validated = $request->validate([
            'intent_id' => ['required', 'integer', 'exists:child_attendance_intents,id'],
        ]);

        $user = $request->user();
        if (!in_array((string)($user?->role ?? ''), ['admin', 'staff'], true)) {
            abort(403, 'This action is unauthorized.');
        }

        $intent = ChildAttendanceIntent::query()->findOrFail((int)$validated['intent_id']);
        $intentDate = optional($intent->date)->toDateString();
        if (!$intentDate) {
            $intentDate = now()->toDateString();
        }

        if ($intentDate !== now()->toDateString()) {
            return $this->manualStatusErrorResponse($request, '管理側で欠席にできるのは当日分のみです。');
        }

        $hasAttendance = Attendance::query()
            ->where('child_id', (int)$intent->child_id)
            ->whereDate('attended_at', $intentDate)
            ->exists();

        if ($hasAttendance) {
            return $this->manualStatusErrorResponse($request, 'すでに出席記録があるため欠席に変更できません。');
        }

        $this->persistManualStatus($intent, 'not_arrived');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'intent_id' => (int)$intent->id,
                'manual_status' => $intent->manual_status,
                'manual_updated_at' => $intent->manual_updated_at
                    ? $intent->manual_updated_at->toISOString()
                    : null,
            ]);
        }

        return back()->with('success', '欠席にしました。');
    }

    /**
     * ✅ 送迎：乗車確認（車アイコンでトグル）
     * - デフォルト未乗車（pickup_confirmed = 0）
     * - 押すたびに 0/1 を反転
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

        return back()->with('success', $intent->pickup_confirmed ? '乗車済みにしました。' : '未乗車に戻しました。');
    }

}
