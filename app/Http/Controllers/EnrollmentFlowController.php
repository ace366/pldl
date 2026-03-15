<?php

namespace App\Http\Controllers;

use App\Models\Base;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnrollmentFlowController extends Controller
{
    public function create()
    {
        $schools = School::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $bases = Base::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('enroll.create', compact('schools', 'bases'));
    }

    public function confirm(Request $request)
    {
        $data = $this->validatedPayload($request);

        // 確認画面で使う学校名/拠点名を付与
        $schoolName = School::find($data['child']['school_id'])?->name ?? '—';
        $baseName   = !empty($data['child']['base_id'])
            ? (Base::find($data['child']['base_id'])?->name ?? '—')
            : '未設定';

        // セッションに保持（confirm → store で改ざん防止）
        $request->session()->put('enroll.payload', $data);

        return view('enroll.confirm', [
            'payload' => $data,
            'schoolName' => $schoolName,
            'baseName' => $baseName,
        ]);
    }

    public function store(Request $request)
    {
        /** @var array|null $payload */
        $payload = $request->session()->get('enroll.payload');
        if (!$payload) {
            return redirect()->route('enroll.create')
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // 念のためサーバー側でも再検証（本番は二段構えが安全）
        $payload = $this->validatedPayload(new Request($payload));

        $child = DB::transaction(function () use ($payload) {
            // --- Child ---
            $childData = $payload['child'];

            // child_code を4桁で発行（重複回避）
            $childCode = $this->generateUniqueChildCode();

            $child = Child::create([
                'child_code'      => $childCode,
                'last_name'       => $childData['last_name'],
                'first_name'      => $childData['first_name'],
                'last_name_kana'  => $childData['last_name_kana'] ?? null,
                'first_name_kana' => $childData['first_name_kana'] ?? null,
                'name'            => trim($childData['last_name'].' '.$childData['first_name']),
                'grade'           => (int)$childData['grade'],
                'school_id'       => (int)$childData['school_id'],
                'base_id'         => !empty($childData['base_id']) ? (int)$childData['base_id'] : null,
                'status'          => 'enrolled',
                'note'            => $childData['note'] ?? null,
            ]);

            // --- Guardian（重複しやすいので “それっぽいキー” で既存を探す） ---
            $g = $payload['guardian'];

            $lookup = Guardian::query();

            $email = $g['email'] ?? null;
            $phone = $g['phone'] ?? null;
            $line  = $g['line_user_id'] ?? null;

            if ($line) {
                $lookup->orWhere('line_user_id', $line);
            }
            if ($email) {
                $lookup->orWhere('email', $email);
            }
            if ($phone) {
                $lookup->orWhere('phone', $phone);
            }

            $guardian = ($line || $email || $phone) ? $lookup->first() : null;

            // preferred_contact が未指定なら、自動決定（line > email > phone）
            $preferred = $g['preferred_contact'] ?? null;
            if (!$preferred) {
                if ($line) $preferred = 'line';
                elseif ($email) $preferred = 'email';
                elseif ($phone) $preferred = 'phone';
            }

            if (!$guardian) {
                $guardian = Guardian::create([
                    'last_name'       => $g['last_name'],
                    'first_name'      => $g['first_name'],
                    'last_name_kana'  => $g['last_name_kana'] ?? null,
                    'first_name_kana' => $g['first_name_kana'] ?? null,
                    'name'            => trim($g['last_name'].' '.$g['first_name']),
                    'line_user_id'    => $line ?: null,
                    'email'           => $email ?: null,
                    'phone'           => $phone ?: null,
                    'preferred_contact' => $preferred,
                ]);
            } else {
                // 既存が見つかった場合：空欄を埋めるだけ（上書き事故を防ぐ）
                $guardian->fill([
                    'last_name'       => $guardian->last_name ?: $g['last_name'],
                    'first_name'      => $guardian->first_name ?: $g['first_name'],
                    'last_name_kana'  => $guardian->last_name_kana ?: ($g['last_name_kana'] ?? null),
                    'first_name_kana' => $guardian->first_name_kana ?: ($g['first_name_kana'] ?? null),
                    'name'            => $guardian->name ?: trim($g['last_name'].' '.$g['first_name']),
                    'line_user_id'    => $guardian->line_user_id ?: ($line ?: null),
                    'email'           => $guardian->email ?: ($email ?: null),
                    'phone'           => $guardian->phone ?: ($phone ?: null),
                    'preferred_contact' => $guardian->preferred_contact ?: $preferred,
                ])->save();
            }

            // --- Pivot（child_guardian） ---
            $relationship = $payload['link']['relationship'] ?? null;

            // テーブルに relationship / relation が両方あるので “両対応” で保存
            $pivot = [
                'relationship' => $relationship,
                'relation'     => $relationship,
            ];

            // 既に紐づいてても update できるように syncWithoutDetaching + pivot更新
            $child->guardians()->syncWithoutDetaching([
                $guardian->id => $pivot
            ]);

            return $child;
        });

        $request->session()->forget('enroll.payload');

        return redirect()->route('enroll.complete', $child);
    }

    public function complete(Child $child)
    {
        $child->loadMissing('school');

        return view('enroll.complete', [
            'child' => $child,
            'qrText' => 'CHILD:'.$child->child_code,
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        // Request がネスト配列を持っていないケースがあるので吸い上げ
        $child    = $request->input('child', $request->get('child', []));
        $guardian = $request->input('guardian', $request->get('guardian', []));
        $link     = $request->input('link', $request->get('link', []));

        // 電話はサーバー側でも数字だけへ
        if (isset($guardian['phone'])) {
            $guardian['phone'] = preg_replace('/\D+/', '', (string)$guardian['phone']);
        }

        $tmp = new Request([
            'child' => $child,
            'guardian' => $guardian,
            'link' => $link,
        ]);

        $v = validator($tmp->all(), [
            // child
            'child.last_name' => ['required','string','max:50'],
            'child.first_name' => ['required','string','max:50'],
            'child.last_name_kana' => ['nullable','string','max:50'],
            'child.first_name_kana' => ['nullable','string','max:50'],
            'child.grade' => ['required','integer','min:1','max:6'],
            'child.school_id' => ['required','integer', Rule::exists('schools','id')],
            'child.base_id' => ['nullable','integer', Rule::exists('bases','id')],
            'child.note' => ['nullable','string','max:1000'],

            // guardian
            'guardian.last_name' => ['required','string','max:50'],
            'guardian.first_name' => ['required','string','max:50'],
            'guardian.last_name_kana' => ['nullable','string','max:50'],
            'guardian.first_name_kana' => ['nullable','string','max:50'],
            'guardian.email' => ['nullable','email','max:255'],
            'guardian.phone' => ['nullable','digits_between:10,11'],
            'guardian.line_user_id' => ['nullable','string','max:80'],
            'guardian.preferred_contact' => ['nullable', Rule::in(['line','email','phone'])],

            // link
            'link.relationship' => ['nullable','string','max:30'],
        ]);

        $data = $v->validate();

        // 連絡先は最低1つ必須（メール/電話/LINE）
        $g = $data['guardian'];
        $hasAny = !empty($g['email']) || !empty($g['phone']) || !empty($g['line_user_id']);
        if (!$hasAny) {
            $v->errors()->add('guardian.preferred_contact', 'メール/電話/LINE のいずれか1つは必須です。');
            abort(back()->withErrors($v)->withInput()->getStatusCode());
        }

        return $data;
    }

    private function generateUniqueChildCode(): string
    {
        // 0000 は避ける
        for ($i=0; $i<30; $i++) {
            $code = str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Child::query()->where('child_code', $code)->exists();
            if (!$exists) return $code;
        }

        // 万一詰まったら例外（極稀）
        throw new \RuntimeException('child_code の発行に失敗しました。');
    }
}
