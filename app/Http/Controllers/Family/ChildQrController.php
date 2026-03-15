<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\ChildAttendanceIntent;
use App\Support\FamilyChildContext;
use Illuminate\Http\Request;

class ChildQrController extends Controller
{
    public function show(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $child = $ctx['activeChild'];
        $siblings = $ctx['siblings'];

        // QRに埋め込む値：将来の拡張を考え prefix を付ける（スキャン側で判定しやすい）
        // child_code は 4桁なので見せる用途に向く
        $payload = 'CHILD:' . ($child->child_code ?? $child->id);

        return view('family.child_qr', [
            'child'   => $child,
            'siblings' => $siblings,
            'payload' => $payload,
            'statusUrl' => route('family.child.qr.status'),
        ]);
    }

    public function status(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $childId = (int)$ctx['activeChild']->id;

        $today = now()->toDateString();

        $hasIn = Attendance::query()
            ->where('child_id', $childId)
            ->whereDate('attended_at', $today)
            ->where(function ($q) {
                $q->whereNull('attendance_type')
                    ->orWhere('attendance_type', 'in');
            })
            ->exists();

        $hasOut = Attendance::query()
            ->where('child_id', $childId)
            ->whereDate('attended_at', $today)
            ->where('attendance_type', 'out')
            ->exists();

        $attendance = Attendance::query()
            ->where('child_id', $childId)
            ->whereDate('attended_at', $today)
            ->orderByDesc('attended_at')
            ->first();

        $intent = ChildAttendanceIntent::query()
            ->where('child_id', $childId)
            ->whereDate('date', $today)
            ->orderByDesc('id')
            ->first();

        $pickupConfirmed = (bool)($intent?->pickup_confirmed);
        $manualArrived = (bool)($intent?->manual_status === 'arrived');
        $attending = ($hasIn || $manualArrived) && !$hasOut;
        $state = 'none';
        if ($hasOut) {
            $state = 'none';
        } elseif ($attending) {
            $state = 'attending';
        } elseif ($pickupConfirmed) {
            $state = 'pickup';
        }

        return response()->json([
            'ok' => true,
            'date' => $today,
            'state' => $state,
            'attending' => $attending,
            'time' => $attendance?->attended_at?->format('H:i'),
            'pickup_confirmed' => $pickupConfirmed,
            'pickup_time' => $intent?->pickup_confirmed_at?->format('H:i'),
        ]);
    }
}
