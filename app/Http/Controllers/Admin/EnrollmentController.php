<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Base;
class EnrollmentController extends Controller
{
    public function create()
    {
        // 学校一覧（表示順は好みでOK）
        $schools = School::query()
            ->orderBy('name')
            ->get();

        // もし拠点もフォームで使ってるなら一緒に渡す（使ってないなら消してOK）
        $bases = Base::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('enroll.create', compact('schools', 'bases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // child
            'child.name'   => ['required', 'string', 'max:100'],
            'child.grade'  => ['required', 'string', 'max:20'],
            'child.base'   => ['required', 'string', 'max:100'],
            'child.status' => ['required', 'in:active,inactive'],

            // guardians (複数)
            'guardians' => ['required', 'array', 'min:1', 'max:5'],
            'guardians.*.name' => ['required', 'string', 'max:100'],
            'guardians.*.line_user_id' => ['nullable', 'string', 'max:64'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.phone' => ['nullable', 'string', 'max:30'],
            'guardians.*.preferred_contact' => ['nullable', 'in:line,email,phone'],
            'guardians.*.relation' => ['nullable', 'string', 'max:30'],

            // 連絡先は最低1つ入ってほしい（任意だが推奨）
        ]);

        DB::transaction(function () use ($validated) {
            /** @var \App\Models\Child $child */
            $child = Child::create($validated['child']);

            foreach ($validated['guardians'] as $g) {
                // 空の保護者行（JSで増やす）対策：名前必須なので基本通らないが念のため
                $guardian = Guardian::create([
                    'name'              => $g['name'],
                    'line_user_id'       => $g['line_user_id'] ?? null,
                    'email'             => $g['email'] ?? null,
                    'phone'             => $g['phone'] ?? null,
                    'preferred_contact' => $g['preferred_contact'] ?? null,
                ]);

                $child->guardians()->attach($guardian->id, [
                    'relation' => $g['relation'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.enroll.create')->with('success', '登録しました。');
    }
}
