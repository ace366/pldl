<?php

namespace App\Http\Controllers\Enroll;

use App\Http\Controllers\Controller;
use App\Models\Base;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnrollCompleteMail;

class EnrollController extends Controller
{
    public function create(Request $request)
    {
        // confirm から「修正する」で戻ってきた時、入力を復元（セッションが生きていれば）
        if ($payload = $request->session()->get('enroll_payload')) {
            $request->session()->flashInput($payload);
        }

        $bases = Base::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schools = School::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('enroll.create', compact('bases', 'schools'));
    }

    public function confirm(Request $request)
    {
        $data = $this->validateAndNormalize($request);

        // confirm → create の「修正する」戻り用にセッションへ（なくても動くが便利）
        $request->session()->put('enroll_payload', $data);

        // confirm画面表示用の名称（任意：無ければ "—" で表示される）
        $schoolName = null;
        if (!empty($data['child']['school_id'])) {
            $schoolName = School::where('id', (int)$data['child']['school_id'])->value('name');
        }

        $baseName = '—';
        if (!empty($data['child']['base_id'])) {
            $baseName = Base::where('id', (int)$data['child']['base_id'])->value('name') ?: '—';
        }

        return view('enroll.confirm', [
            'data'       => $data,
            'schoolName' => $schoolName,
            'baseName'   => $baseName,
        ]);
    }

    public function store(Request $request)
    {
        // ✅ confirm から hidden で受け取った値を、改めてバリデーションして確定させる
        $data = $this->validateAndNormalize($request);

        $result = DB::transaction(function () use ($data) {
            // ---- 保護者作成 ----
            $g = new Guardian();
            $g->last_name       = $data['guardian']['last_name'];
            $g->first_name      = $data['guardian']['first_name'];
            $g->last_name_kana  = $data['guardian']['last_name_kana'] ?? null;
            $g->first_name_kana = $data['guardian']['first_name_kana'] ?? null;
            $g->name            = trim($g->last_name . ' ' . $g->first_name);

            $g->email             = $data['guardian']['email'] ?? null;
            $g->phone             = $data['guardian']['phone'] ?? null; // 数字のみ（10/11桁）に正規化済み
            $g->emergency_phone   = $data['guardian']['emergency_phone'] ?? null;
            $g->line_user_id      = $data['guardian']['line_user_id'] ?? null;
            $g->preferred_contact = $data['guardian']['preferred_contact'] ?? null;
            $g->save();

            // ---- 児童作成 ----
            $childCode = $this->generateChildCode();

            $c = new Child();
            $c->child_code      = $childCode;
            $c->last_name       = $data['child']['last_name'];
            $c->first_name      = $data['child']['first_name'];
            $c->last_name_kana  = $data['child']['last_name_kana'] ?? null;
            $c->first_name_kana = $data['child']['first_name_kana'] ?? null;
            $c->name            = trim($c->last_name . ' ' . $c->first_name);

            $c->grade     = (int)$data['child']['grade'];
            $c->school_id = (int)$data['child']['school_id'];

            // ★拠点は必須化したので必ず入る想定
            $c->base_id = (int)$data['child']['base_id'];

            // ★追加：生年月日（children.birth_date カラムがある想定）
            $c->birth_date = $data['child']['birth_date'];

            // ★追加：アレルギー（children.has_allergy / children.allergy_note がある想定）
            $c->has_allergy  = (int)$data['child']['has_allergy'];
            $c->allergy_note = $data['child']['allergy_note'] ?? null;

            // status は enrolled 固定（運用に合わせて変更可）
            $c->status = 'enrolled';

            // 備考はアレルギー以外
            $c->note = $data['child']['note'] ?? null;

            // children.base カラムが存在するので「拠点名」も入れておく（混在対策）
            $c->base = null;
            if ($c->base_id) {
                $baseName = Base::where('id', $c->base_id)->value('name');
                $c->base = $baseName ?: null;
            }

            $c->save();

            // ---- 紐づけ（relationship と relation の両方保存）----
            $rel = $data['link']['relationship'] ?? null;

            $c->guardians()->attach($g->id, [
                'relationship' => $rel,
                'relation'     => $rel, // DBにrelationもあるので両方埋める
            ]);

            return [
                'child_id'    => $c->id,
                'child_code'  => $c->child_code,
                'guardian_id' => $g->id,
            ];
        });

        // confirm戻り用セッションは不要なので破棄
        $request->session()->forget('enroll_payload');

        // ✅ 登録した児童として「ご家庭ログイン状態」にする（FamilyAuthController@login と同じ考え方）
        $request->session()->put('family_child_id', (int)$result['child_id']);

        // セッション固定化攻撃対策（loginと同じ）
        $request->session()->regenerate();

        // ✅ 登録完了メールを送信（失敗しても登録は継続）
        try {
            Mail::to($data['guardian']['email'])
                ->send(new EnrollCompleteMail([
                    'guardian_name' => trim(($data['guardian']['last_name'] ?? '') . ' ' . ($data['guardian']['first_name'] ?? '')),
                    'child_name'    => trim(($data['child']['last_name'] ?? '') . ' ' . ($data['child']['first_name'] ?? '')),
                    'child_code'    => $result['child_code'] ?? null,
                    'line_url'      => 'https://lin.ee/tmOA7d8',
                    'line_img'      => 'https://scdn.line-apps.com/n/line_add_friends/btn/ja.png',
                ]));
        } catch (\Throwable $e) {
            logger()->warning('Enroll complete mail failed', ['error' => $e->getMessage()]);
        }

        // ✅ 登録完了ページへ（表示用データは一度だけ使う）
        $request->session()->put('enroll_complete', [
            'login_id' => $result['child_code'],
        ]);

        return redirect()->route('enroll.complete');
    }

    public function complete(Request $request)
    {
        $payload = $request->session()->pull('enroll_complete');

        if (!$payload || empty($payload['login_id'])) {
            return redirect()->route('enroll.create');
        }

        return view('enroll.complete', [
            'loginId' => $payload['login_id'],
        ]);
    }

    private function validateAndNormalize(Request $request): array
    {
        $validated = $request->validate([
            // child
            'child.last_name'       => ['required', 'string', 'max:50'],
            'child.first_name'      => ['required', 'string', 'max:50'],
            'child.last_name_kana'  => ['nullable', 'string', 'max:50'],
            'child.first_name_kana' => ['nullable', 'string', 'max:50'],

            // ★追加：生年月日（未来日禁止）
            'child.birth_date'      => ['required', 'date', 'before_or_equal:today'],

            'child.grade'           => ['required', 'integer', 'min:1', 'max:6'],
            'child.school_id'       => ['required', 'integer', 'exists:schools,id'],

            // ★必須化：拠点
            'child.base_id'         => ['required', 'integer', 'exists:bases,id'],

            // ★追加：アレルギー
            'child.has_allergy'     => ['required', 'in:0,1'],
            'child.allergy_note'    => ['nullable', 'string', 'max:1000'],

            // 備考（アレルギー以外）
            'child.note'            => ['nullable', 'string', 'max:1000'],

            // guardian
            'guardian.last_name'        => ['required', 'string', 'max:50'],
            'guardian.first_name'       => ['required', 'string', 'max:50'],
            'guardian.last_name_kana'   => ['nullable', 'string', 'max:50'],
            'guardian.first_name_kana'  => ['nullable', 'string', 'max:50'],
            'guardian.email'            => ['required', 'email', 'max:255'],
            'guardian.phone'            => ['required', 'string', 'max:30'],
            'guardian.emergency_phone'  => ['nullable', 'string', 'max:30'],
            'guardian.line_user_id'     => ['nullable', 'string', 'max:80'],
            'guardian.preferred_contact'=> ['nullable', 'in:email,phone'],

            // link
            'link.relationship' => ['nullable', 'string', 'max:30'],
        ], [], [
            'child.last_name'      => '児童：姓',
            'child.first_name'     => '児童：名',
            'child.birth_date'     => '児童：生年月日',
            'child.grade'          => '児童：学年',
            'child.school_id'      => '児童：学校',
            'child.base_id'        => '児童：拠点',
            'child.has_allergy'    => '児童：アレルギー',
            'child.allergy_note'   => '児童：アレルギー内容',
            'guardian.last_name'   => '保護者：姓',
            'guardian.first_name'  => '保護者：名',
        ]);

        // -------------------------
        // アレルギー：有のときだけ内容必須
        // -------------------------
        $hasAllergy = (string)($validated['child']['has_allergy'] ?? '0');
        $allergyNote = trim((string)($validated['child']['allergy_note'] ?? ''));

        if ($hasAllergy === '1' && $allergyNote === '') {
            return back()
                ->withErrors(['child.allergy_note' => 'アレルギーが「有」の場合は、内容を入力してください。'])
                ->withInput()
                ->throwResponse();
        }
        if ($hasAllergy !== '1') {
            // 無のときは内容をnull扱い
            $validated['child']['allergy_note'] = null;
        }

        // -------------------------
        // 電話：数字以外除去（ハイフン等を全て消す）
        // -------------------------
        $phoneRaw = (string)($validated['guardian']['phone'] ?? '');
        $phone = preg_replace('/\D+/', '', $phoneRaw) ?: null;

        // みどり市想定：携帯(070/080/090=11桁) or 固定(0277=10桁 or 11桁)
        if ($phone !== null) {
            $len = strlen($phone);

            // 桁数チェック（10 or 11）
            if (!in_array($len, [10, 11], true)) {
                return back()
                    ->withErrors(['guardian.phone' => '電話番号は10桁または11桁の数字で入力してください。'])
                    ->withInput()
                    ->throwResponse();
            }

            $isMobile = ($len === 11) && (
                str_starts_with($phone, '070') ||
                str_starts_with($phone, '080') ||
                str_starts_with($phone, '090')
            );

            $isFixed0277 = str_starts_with($phone, '0277'); // 10桁/11桁どちらも許容

            if (!($isMobile || $isFixed0277)) {
                return back()
                    ->withErrors(['guardian.phone' => 'みどり市想定の電話番号として、携帯（070/080/090）または固定（0277）で入力してください。'])
                    ->withInput()
                    ->throwResponse();
            }
        }

        $validated['guardian']['phone'] = $phone;

        // -------------------------
        // 緊急連絡先（任意）：数字のみ10〜11桁
        // -------------------------
        $emergencyRaw = (string)($validated['guardian']['emergency_phone'] ?? '');
        $emergencyPhone = preg_replace('/\D+/', '', $emergencyRaw) ?: null;

        if ($emergencyPhone !== null) {
            $len = strlen($emergencyPhone);
            if (!in_array($len, [10, 11], true)) {
                return back()
                    ->withErrors(['guardian.emergency_phone' => '緊急連絡先は10桁または11桁の数字で入力してください。'])
                    ->withInput()
                    ->throwResponse();
            }
        }

        $validated['guardian']['emergency_phone'] = $emergencyPhone;

        // -------------------------
        // 連絡手段：email/phone/line のどれか1つ以上必須
        // -------------------------
        $hasEmail = !empty($validated['guardian']['email']);
        $hasPhone = !empty($validated['guardian']['phone']);
        $hasLine  = !empty($validated['guardian']['line_user_id']);

        if (!($hasEmail && $hasPhone)) {
            return back()
                ->withErrors(['guardian.preferred_contact' => 'メールと電話は必須です。'])
                ->withInput()
                ->throwResponse();
        }

        // preferred_contact 未選択なら自動推定
        if (empty($validated['guardian']['preferred_contact'])) {
            $validated['guardian']['preferred_contact'] = 'phone';
        }

        return $validated;
    }

    private function generateChildCode(): string
    {
        // 4桁のユニークコード（0000〜9999は避けたければ範囲調整）
        for ($i = 0; $i < 50; $i++) {
            $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Child::where('child_code', $code)->exists();
            if (!$exists) {
                return $code;
            }
        }

        // レアケース（枯渇等）
        abort(500, 'child_code の採番に失敗しました。管理者に連絡してください。');
    }
}
