<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Base;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FamilyRegistrationController extends Controller
{
    public function create()
    {
        // select用（あなたの既存テーブルに合わせて）
        $schools = School::query()->orderBy('name')->get();
        $bases   = Base::query()->orderBy('name')->get();

        return view('family.register', compact('schools', 'bases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // ---- 児童(Child) ----
            'child.last_name'       => ['required', 'string', 'max:50'],
            'child.first_name'      => ['required', 'string', 'max:50'],
            'child.last_name_kana'  => ['nullable', 'string', 'max:50'],
            'child.first_name_kana' => ['nullable', 'string', 'max:50'],
            'child.grade'           => ['required', 'integer', 'min:1', 'max:6'],
            'child.school_id'       => ['required', 'integer', Rule::exists('schools', 'id')],
            'child.base_id'         => ['nullable', 'integer', Rule::exists('bases', 'id')],
            'child.status'          => ['required', Rule::in(['enrolled','withdrawn'])],
            'child.child_code'      => [
                'required',
                'regex:/^\d{4}$/',
                Rule::unique('children', 'child_code'),
            ],
            'child.note'            => ['nullable', 'string', 'max:1000'],

            // ---- 保護者(Guardian) ----
            'guardian.last_name'       => ['required', 'string', 'max:50'],
            'guardian.first_name'      => ['required', 'string', 'max:50'],
            'guardian.last_name_kana'  => ['nullable', 'string', 'max:50'],
            'guardian.first_name_kana' => ['nullable', 'string', 'max:50'],

            // 連絡先：最低1つ必須（後でafterで判定）
            'guardian.email'        => ['nullable', 'email', 'max:255'],
            'guardian.phone'        => ['nullable', 'regex:/^\d{10,11}$/'], // 数字のみ10-11桁
            'guardian.line_user_id' => ['nullable', 'string', 'max:80'],

            'guardian.preferred_contact' => ['nullable', Rule::in(['line','email','phone'])],

            // ---- 紐づけ(relationship) ----
            'relationship' => ['nullable', 'string', 'max:30'],
        ], [
            'child.child_code.regex' => '児童IDは4桁の数字で入力してください。',
            'guardian.phone.regex'   => '電話番号は数字のみ（10〜11桁）で入力してください。',
        ]);

        // 連絡先の最低1つ必須
        $hasAnyContact = !empty($validated['guardian']['email'])
            || !empty($validated['guardian']['phone'])
            || !empty($validated['guardian']['line_user_id']);

        if (!$hasAnyContact) {
            return back()
                ->withErrors(['guardian.contact' => 'メール / 電話 / LINE userId のいずれか1つは必須です。'])
                ->withInput();
        }

        // preferred_contact が指定されているなら、実体も必須
        $pref = $validated['guardian']['preferred_contact'] ?? null;
        if ($pref === 'email' && empty($validated['guardian']['email'])) {
            return back()->withErrors(['guardian.preferred_contact' => '優先連絡手段がメールの場合、メールを入力してください。'])->withInput();
        }
        if ($pref === 'phone' && empty($validated['guardian']['phone'])) {
            return back()->withErrors(['guardian.preferred_contact' => '優先連絡手段が電話の場合、電話を入力してください。'])->withInput();
        }
        if ($pref === 'line' && empty($validated['guardian']['line_user_id'])) {
            return back()->withErrors(['guardian.preferred_contact' => '優先連絡手段がLINEの場合、LINE userIdを入力してください。'])->withInput();
        }

        // 保存（混在防止：テーブルごとに明示的にマッピング）
        $child = null;
        $guardian = null;

        DB::transaction(function () use ($validated, &$child, &$guardian) {
            // Guardian.name が必須だった件に対応（自動生成して必ず埋める）
            $guardianName = trim(($validated['guardian']['last_name'] ?? '').' '.($validated['guardian']['first_name'] ?? ''));

            $guardian = Guardian::create([
                'last_name'       => $validated['guardian']['last_name'],
                'first_name'      => $validated['guardian']['first_name'],
                'last_name_kana'  => $validated['guardian']['last_name_kana'] ?? null,
                'first_name_kana' => $validated['guardian']['first_name_kana'] ?? null,
                'email'           => $validated['guardian']['email'] ?? null,
                'phone'           => $validated['guardian']['phone'] ?? null,
                'line_user_id'    => $validated['guardian']['line_user_id'] ?? null,
                'preferred_contact' => $validated['guardian']['preferred_contact'] ?? null,

                // ★ここが重要：guardians.name を必ず埋める
                'name'            => $guardianName,
            ]);

            $child = Child::create([
                'last_name'       => $validated['child']['last_name'],
                'first_name'      => $validated['child']['first_name'],
                'last_name_kana'  => $validated['child']['last_name_kana'] ?? null,
                'first_name_kana' => $validated['child']['first_name_kana'] ?? null,
                'grade'           => $validated['child']['grade'],
                'school_id'       => $validated['child']['school_id'],
                'base_id'         => $validated['child']['base_id'] ?? null,
                'status'          => $validated['child']['status'],
                'child_code'      => $validated['child']['child_code'],
                'note'            => $validated['child']['note'] ?? null,
            ]);

            // pivot（relationshipも保存）
            $child->guardians()->attach($guardian->id, [
                'relationship' => $validated['relationship'] ?? null,
            ]);
        });

        // 登録完了（必要なら完了画面に飛ばす）
        return redirect()->route('family.register')
            ->with('success', '登録が完了しました。児童ID（4桁）でQR出席登録できます。');
    }
}
