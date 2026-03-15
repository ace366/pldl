<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceIntentReactController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        return view('admin.attendance_intents.react', [
            'date' => $date,
            'apiSummaryUrl'      => route('admin.attendance_intents.api.summary'),
            'apiTogglePickupUrl' => route('admin.attendance_intents.api.toggle_pickup'),
            'apiToggleManualUrl' => route('admin.attendance_intents.api.toggle_manual'),
        ]);
    }
}
