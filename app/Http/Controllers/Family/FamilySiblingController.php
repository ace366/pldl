<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Child;
use App\Models\School;
use App\Support\FamilyChildContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FamilySiblingController extends Controller
{
    public function index(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $siblings = $ctx['siblings'];

        $schools = School::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $bases = Base::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $props = [
            'loginCode' => (string)$ctx['familyLoginCode'],
            'siblings' => $siblings->map(function ($s) {
                return [
                    'id' => (int)$s->id,
                    'name' => $s->full_name,
                    'kana' => trim((string)$s->last_name_kana . ' ' . (string)$s->first_name_kana),
                    'grade' => (int)($s->grade ?? 0),
                    'school' => (string)($s->school?->name ?? '学校未設定'),
                    'base' => (string)($s->baseMaster?->name ?? '拠点未設定'),
                    'qrUrl' => route('family.child.qr', ['child_id' => (int)$s->id]),
                    'availabilityUrl' => route('family.availability.index', ['child_id' => (int)$s->id]),
                ];
            })->values(),
            'schools' => $schools->map(fn ($s) => ['id' => (int)$s->id, 'name' => (string)$s->name])->values(),
            'bases' => $bases->map(fn ($b) => ['id' => (int)$b->id, 'name' => (string)$b->name])->values(),
            'routes' => [
                'store' => route('family.siblings.store'),
            ],
            'csrf' => csrf_token(),
            'old' => (object)$request->session()->getOldInput(),
            'errors' => (object)$request->session()->get('errors')?->toArray(),
        ];

        return view('family.siblings.react', compact('props'));
    }

    public function store(Request $request)
    {
        $ctx = FamilyChildContext::resolve($request);
        $loginChild = Child::with('guardians')->findOrFail((int)$ctx['loginChild']->id);

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name_kana' => ['nullable', 'string', 'max:50'],
            'first_name_kana' => ['nullable', 'string', 'max:50'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'grade' => ['required', 'integer', 'min:1', 'max:6'],
            'birth_date' => ['required', 'date'],
            'base_id' => ['nullable', 'integer', 'exists:bases,id'],
            'has_allergy' => ['nullable', 'in:0,1'],
            'allergy_note' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'birth_date.required' => '生年月日を入力してください。',
        ]);

        $hasAllergy = (int)($validated['has_allergy'] ?? 0) === 1;
        if ($hasAllergy && trim((string)($validated['allergy_note'] ?? '')) === '') {
            return back()
                ->withErrors(['allergy_note' => 'アレルギーがある場合は内容を入力してください。'])
                ->withInput();
        }
        if (!$hasAllergy) {
            $validated['allergy_note'] = null;
        }

        $childCode = $this->generateChildCode();
        $hasFamilyLoginCode = Schema::hasColumn('children', 'family_login_code');

        $child = null;
        DB::transaction(function () use ($validated, $hasAllergy, $childCode, $hasFamilyLoginCode, $ctx, $loginChild, &$child) {
            $payload = [
                'child_code' => $childCode,
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'last_name_kana' => $validated['last_name_kana'] ?? null,
                'first_name_kana' => $validated['first_name_kana'] ?? null,
                'name' => trim(($validated['last_name'] ?? '').' '.($validated['first_name'] ?? '')),
                'birth_date' => $validated['birth_date'],
                'grade' => (int)$validated['grade'],
                'school_id' => (int)$validated['school_id'],
                'base_id' => !empty($validated['base_id']) ? (int)$validated['base_id'] : null,
                'has_allergy' => $hasAllergy ? 1 : 0,
                'allergy_note' => $validated['allergy_note'] ?? null,
                'status' => 'enrolled',
                'note' => $validated['note'] ?? null,
            ];

            if ($hasFamilyLoginCode) {
                $payload['family_login_code'] = (string)$ctx['familyLoginCode'];
            }

            if (Schema::hasColumn('children', 'base')) {
                $payload['base'] = !empty($validated['base_id'])
                    ? (string)(Base::query()->whereKey((int)$validated['base_id'])->value('name') ?? '')
                    : null;
            }

            $child = Child::create($payload);

            $syncData = [];
            foreach ($loginChild->guardians as $g) {
                $rel = $g->pivot?->relationship ?? $g->pivot?->relation ?? null;
                $syncData[(int)$g->id] = [
                    'relationship' => $rel,
                    'relation' => $rel,
                ];
            }
            if (!empty($syncData)) {
                $child->guardians()->syncWithoutDetaching($syncData);
            }
        });

        $request->session()->put('family_active_child_id', (int)$child->id);

        return redirect()
            ->route('family.child.qr', ['child_id' => (int)$child->id])
            ->with('success', 'きょうだいを登録しました。');
    }

    private function generateChildCode(): string
    {
        for ($i = 0; $i < 300; $i++) {
            $n = random_int(1, 9999);
            $code = str_pad((string)$n, 4, '0', STR_PAD_LEFT);
            if (!Child::query()->where('child_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('child_code の採番に失敗しました。');
    }
}
