<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ChildGuardianEnrollmentController extends Controller
{
    public function create()
    {
        return view('admin.enrollments.child_guardian_create', [
            'bases'   => Base::query()->orderBy('name')->get(),
            'schools' => School::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // --- child ---
            'child.child_code'        => ['nullable', 'digits:4', 'unique:children,child_code'],
            'child.last_name'         => ['required', 'string', 'max:50'],
            'child.first_name'        => ['required', 'string', 'max:50'],
            'child.last_name_kana'    => ['nullable', 'string', 'max:50'],
            'child.first_name_kana'   => ['nullable', 'string', 'max:50'],
            'child.grade'             => ['required', 'integer', 'min:1', 'max:6'],
            'child.base_id'           => ['nullable', 'integer', 'exists:bases,id'],
            'child.school_id'         => ['nullable', 'integer', 'exists:schools,id'],
            'child.status'            => ['required', 'in:enrolled,withdrawn'],
            'child.note'              => ['nullable', 'string', 'max:2000'],

            // --- guardian ---
            'guardian.last_name'       => ['required', 'string', 'max:50'],
            'guardian.first_name'      => ['required', 'string', 'max:50'],
            'guardian.last_name_kana'  => ['nullable', 'string', 'max:50'],
            'guardian.first_name_kana' => ['nullable', 'string', 'max:50'],
            'guardian.email'           => ['nullable', 'email', 'max:255'],
            'guardian.phone'           => ['nullable', 'string', 'max:20'],
            'guardian.line_user_id'    => ['nullable', 'string', 'max:255'],
            'guardian.preferred_contact' => ['nullable', 'in:phone,email,line'],

            // --- pivot ---
            'relationship' => ['nullable', 'string', 'max:30'],
        ]);

        // 電話を数字だけに
        $phoneRaw = (string)($validated['guardian']['phone'] ?? '');
        $phone = preg_replace('/\D/u', '', $phoneRaw);
        $validated['guardian']['phone'] = $phone ?: null;

        $childName = trim(($validated['child']['last_name'] ?? '').' '.($validated['child']['first_name'] ?? ''));
        $guardianName = trim(($validated['guardian']['last_name'] ?? '').' '.($validated['guardian']['first_name'] ?? ''));

        $childCode = $validated['child']['child_code'] ?? null;
        $relationship = $validated['relationship'] ?? null;

        [$child, $guardian] = DB::transaction(function () use ($validated, $childName, $guardianName, $childCode, $relationship) {

            if (!$childCode) {
                $childCode = $this->generateUniqueChildCode();
            }

            $baseName = null;
            if (!empty($validated['child']['base_id'])) {
                $base = Base::find($validated['child']['base_id']);
                $baseName = $base?->name;
            }

            $child = Child::create([
                'child_code'       => $childCode,
                'last_name'        => $validated['child']['last_name'],
                'first_name'       => $validated['child']['first_name'],
                'last_name_kana'   => $validated['child']['last_name_kana'] ?? null,
                'first_name_kana'  => $validated['child']['first_name_kana'] ?? null,
                'name'             => $childName,
                'grade'            => $validated['child']['grade'],
                'base_id'          => $validated['child']['base_id'] ?? null,
                'school_id'        => $validated['child']['school_id'] ?? null,
                'base'             => $baseName, // children.base（文字列）
                'status'           => $validated['child']['status'],
                'note'             => $validated['child']['note'] ?? null,
            ]);

            $guardian = Guardian::create([
                'last_name'         => $validated['guardian']['last_name'],
                'first_name'        => $validated['guardian']['first_name'],
                'last_name_kana'    => $validated['guardian']['last_name_kana'] ?? null,
                'first_name_kana'   => $validated['guardian']['first_name_kana'] ?? null,
                'name'              => $guardianName,
                'line_user_id'      => $validated['guardian']['line_user_id'] ?? null,
                'email'             => $validated['guardian']['email'] ?? null,
                'phone'             => $validated['guardian']['phone'] ?? null,
                'preferred_contact' => $validated['guardian']['preferred_contact'] ?? null,
            ]);

            // pivot：relationship & relation 両方に入れて事故らないように
            $child->guardians()->attach($guardian->id, [
                'relationship' => $relationship,
                'relation'     => $relationship,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return [$child, $guardian];
        });

        // ★保護者確認URL（署名付き・7日有効）
        $confirmUrl = URL::temporarySignedRoute(
            'guardian.confirm',
            now()->addDays(7),
            ['guardian' => $guardian->id]
        );

        // ★完了ページへ誘導（URLを表示するため）
        return redirect()->route('admin.enrollments.child_guardian.completed', [
            'child_id' => $child->id,
            'guardian_id' => $guardian->id,
        ])->with('confirm_url', $confirmUrl);
    }

    // 管理者側：登録完了（URL表示＋誘導）
    public function completed(Request $request)
    {
        $childId = (int)$request->query('child_id');
        $guardianId = (int)$request->query('guardian_id');

        $child = Child::with(['school','base','guardians'])->findOrFail($childId);
        $guardian = Guardian::findOrFail($guardianId);

        $confirmUrl = session('confirm_url'); // store() から渡す

        return view('admin.enrollments.child_guardian_completed', [
            'child' => $child,
            'guardian' => $guardian,
            'confirmUrl' => $confirmUrl,
        ]);
    }

    // 公開：保護者が見る確認ページ（署名URL必須）
    public function confirm(Guardian $guardian)
    {
        // guardian に紐づく児童（必要なら複数も対応）
        // pivotに relationship / relation があるので、両方取れるようにする
        $children = $guardian->children()
            ->with(['school','base'])
            ->get()
            ->map(function ($c) {
                $pivot = $c->pivot ?? null;
                $relationship = $pivot->relationship ?? $pivot->relation ?? null;

                return [
                    'id' => $c->id,
                    'child_code' => $c->child_code,
                    'name' => $c->full_name ?? $c->name ?? trim(($c->last_name ?? '').' '.($c->first_name ?? '')),
                    'grade' => $c->grade,
                    'school' => $c->school?->name ?? '—',
                    'base' => $c->base?->name ?? ($c->base ?? '—'),
                    'relationship' => $relationship,
                ];
            });

        return view('guardian.confirm', [
            'guardian' => $guardian,
            'children' => $children,
        ]);
    }

    private function generateUniqueChildCode(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $code = str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            if (!Child::where('child_code', $code)->exists()) return $code;
        }
        $n = 1;
        while ($n <= 9999) {
            $code = str_pad((string)$n, 4, '0', STR_PAD_LEFT);
            if (!Child::where('child_code', $code)->exists()) return $code;
            $n++;
        }
        throw new \RuntimeException('child_code を採番できませんでした（枯渇）');
    }
}
