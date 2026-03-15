<?php

namespace App\Http\Controllers\Family;

use App\Mail\EnrollCompleteMail;
use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Guardian;
use App\Support\FamilyChildContext;
use App\Support\FamilyGuardianResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FamilyProfileController extends Controller
{
    public function edit(Request $request)
    {
        $childId = (int)$request->session()->get('family_child_id');
        $child = Child::with('guardians')->findOrFail($childId);

        $guardians = $child->guardians->values();
        $requestedGuardianId = (int)$request->query('guardian_id', 0);
        $targetGuardian = $guardians->firstWhere('id', $requestedGuardianId) ?? $guardians->first();
        if ($targetGuardian) {
            FamilyGuardianResolver::setForChild($request, $childId, (int)$targetGuardian->id);
        }
        $avatarVersion = (int)optional($targetGuardian?->updated_at)->timestamp;
        $avatarPreviewUrl = route('family.profile.avatar.show', [
            'child_id' => $childId,
            'v' => $avatarVersion,
        ]);

        return view('family.profile.edit', [
            'child' => $child,
            'guardians' => $guardians,
            'targetGuardian' => $targetGuardian,
            'avatarPreviewUrl' => $avatarPreviewUrl,
        ]);
    }

    public function update(Request $request)
    {
        $childId = (int)$request->session()->get('family_child_id');
        $child = Child::with('guardians:id')->findOrFail($childId);
        $allowedGuardianIds = $child->guardians->pluck('id')->map(fn ($id) => (int)$id)->all();
        $hasEmergencyPhone = Schema::hasColumn('guardians', 'emergency_phone');
        $hasEmergencyPhoneLabel = Schema::hasColumn('guardians', 'emergency_phone_label');

        $phone = preg_replace('/\D+/', '', (string)$request->input('phone', ''));
        $emergencyPhone = $hasEmergencyPhone
            ? preg_replace('/\D+/', '', (string)$request->input('emergency_phone', ''))
            : null;
        $request->merge([
            'phone' => $phone !== '' ? $phone : null,
            'emergency_phone' => $emergencyPhone !== '' ? $emergencyPhone : null,
        ]);

        $rules = [
            'guardian_id' => ['required', 'integer'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name_kana' => ['nullable', 'string', 'max:50'],
            'first_name_kana' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^\d{10,11}$/'],
            'preferred_contact' => ['nullable', 'in:email,phone,line'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
        if ($hasEmergencyPhoneLabel) {
            $rules['emergency_phone_label'] = ['nullable', 'string', 'max:80'];
        }
        if ($hasEmergencyPhone) {
            $rules['emergency_phone'] = ['nullable', 'regex:/^\d{10,11}$/'];
        }

        $validated = $request->validate($rules);

        $guardianId = (int)$validated['guardian_id'];
        abort_unless(in_array($guardianId, $allowedGuardianIds, true), 403);

        $guardian = Guardian::findOrFail($guardianId);
        $update = $validated;
        unset($update['guardian_id'], $update['note']);
        $update['name'] = trim(($update['last_name'] ?? '').' '.($update['first_name'] ?? ''));

        if (\Illuminate\Support\Facades\Schema::hasColumn('guardians', 'name_kana')) {
            $update['name_kana'] = trim(($update['last_name_kana'] ?? '').' '.($update['first_name_kana'] ?? ''));
        }

        DB::transaction(function () use ($guardian, $update, $child, $validated) {
            $guardian->update($update);
            $child->update([
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()
            ->route('family.profile.edit', ['guardian_id' => $guardian->id])
            ->with('success', '登録内容を更新しました。');
    }

    public function storeGuardian(Request $request)
    {
        $childId = (int)$request->session()->get('family_child_id');
        $child = Child::with('guardians:id')->findOrFail($childId);
        $originalGuardian = FamilyGuardianResolver::resolve($request, $childId);
        $hasEmergencyPhone = Schema::hasColumn('guardians', 'emergency_phone');
        $hasEmergencyPhoneLabel = Schema::hasColumn('guardians', 'emergency_phone_label');

        $newPhone = preg_replace('/\D+/', '', (string)$request->input('new_phone', ''));
        $newEmergencyPhone = $hasEmergencyPhone
            ? preg_replace('/\D+/', '', (string)$request->input('new_emergency_phone', ''))
            : null;
        $request->merge([
            'new_phone' => $newPhone !== '' ? $newPhone : null,
            'new_emergency_phone' => $newEmergencyPhone !== '' ? $newEmergencyPhone : null,
        ]);

        $rules = [
            'new_last_name' => ['required', 'string', 'max:50'],
            'new_first_name' => ['required', 'string', 'max:50'],
            'new_last_name_kana' => ['nullable', 'string', 'max:50'],
            'new_first_name_kana' => ['nullable', 'string', 'max:50'],
            'new_email' => ['nullable', 'email', 'max:255'],
            'new_phone' => ['nullable', 'regex:/^\d{10,11}$/'],
            'new_preferred_contact' => ['nullable', 'in:email,phone,line'],
            'new_relationship' => ['nullable', 'string', 'max:30'],
        ];
        if ($hasEmergencyPhoneLabel) {
            $rules['new_emergency_phone_label'] = ['nullable', 'string', 'max:80'];
        }
        if ($hasEmergencyPhone) {
            $rules['new_emergency_phone'] = ['nullable', 'regex:/^\d{10,11}$/'];
        }
        $validated = $request->validate($rules);

        $guardianData = [
            'last_name' => $validated['new_last_name'],
            'first_name' => $validated['new_first_name'],
            'last_name_kana' => $validated['new_last_name_kana'] ?? null,
            'first_name_kana' => $validated['new_first_name_kana'] ?? null,
            'email' => $validated['new_email'] ?? null,
            'phone' => $validated['new_phone'] ?? null,
            'preferred_contact' => $validated['new_preferred_contact'] ?? null,
            'name' => trim(($validated['new_last_name'] ?? '').' '.($validated['new_first_name'] ?? '')),
        ];
        if ($hasEmergencyPhoneLabel) {
            $guardianData['emergency_phone_label'] = $validated['new_emergency_phone_label'] ?? null;
        }
        if ($hasEmergencyPhone) {
            $guardianData['emergency_phone'] = $validated['new_emergency_phone'] ?? null;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('guardians', 'name_kana')) {
            $guardianData['name_kana'] = trim(($validated['new_last_name_kana'] ?? '').' '.($validated['new_first_name_kana'] ?? ''));
        }

        $guardian = null;
        DB::transaction(function () use ($child, $guardianData, $validated, &$guardian) {
            $guardian = Guardian::create($guardianData);
            $relationship = $validated['new_relationship'] ?? null;

            $child->guardians()->attach($guardian->id, [
                'relationship' => $relationship,
                'relation' => $relationship,
            ]);
        });

        // 追加した保護者メール + 元の保護者メールへ登録完了メールを送信
        $mailTargets = [];
        $seen = [];
        $pushMailTarget = static function (?string $email, string $guardianName) use (&$mailTargets, &$seen): void {
            $email = trim((string)$email);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return;
            }
            $key = strtolower($email);
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $mailTargets[] = [
                'email' => $email,
                'guardian_name' => trim($guardianName) !== '' ? trim($guardianName) : '保護者',
            ];
        };

        $pushMailTarget(
            $guardian->email,
            trim(($guardian->last_name ?? '').' '.($guardian->first_name ?? ''))
        );
        $pushMailTarget(
            $originalGuardian?->email,
            trim(($originalGuardian?->last_name ?? '').' '.($originalGuardian?->first_name ?? ''))
        );

        if (!empty($mailTargets)) {
            try {
                foreach ($mailTargets as $target) {
                    Mail::to($target['email'])->send(new EnrollCompleteMail([
                        'guardian_name' => $target['guardian_name'],
                        'child_name'    => trim(($child->last_name ?? '').' '.($child->first_name ?? '')),
                        'child_code'    => $child->child_code ?? null,
                        'line_url'      => 'https://lin.ee/tmOA7d8',
                        'line_img'      => 'https://scdn.line-apps.com/n/line_add_friends/btn/ja.png',
                    ]));
                }
            } catch (\Throwable $e) {
                logger()->warning('Family add guardian complete mail failed', [
                    'child_id' => $childId,
                    'guardian_id' => $guardian?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('family.profile.edit', ['guardian_id' => $guardian->id])
            ->with('success', '保護者情報を追加しました。');
    }

    public function updateAvatar(Request $request)
    {
        if (!Schema::hasColumn('guardians', 'avatar_path')) {
            return back()->withErrors(['avatar' => '画像機能が有効ではありません。管理者にお問い合わせください。']);
        }

        $childId = (int)$request->session()->get('family_child_id');
        $child = Child::with('guardians:id')->findOrFail($childId);
        $allowedGuardianIds = $child->guardians->pluck('id')->map(fn ($id) => (int)$id)->all();

        $validated = $request->validate([
            'guardian_id' => ['required', 'integer'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_avatar' => ['nullable', 'in:0,1'],
        ], [
            'avatar.image' => '画像ファイルを選択してください。',
            'avatar.mimes' => 'jpg / jpeg / png / webp / gif を指定してください。',
            'avatar.max' => '画像サイズは5MB以下にしてください。',
        ]);

        $guardianId = (int)$validated['guardian_id'];
        abort_unless(in_array($guardianId, $allowedGuardianIds, true), 403);

        $guardian = Guardian::findOrFail($guardianId);
        FamilyGuardianResolver::setForChild($request, $childId, $guardianId);

        $remove = (int)($validated['remove_avatar'] ?? 0) === 1;
        $hasUpload = $request->hasFile('avatar');

        if (!$remove && !$hasUpload) {
            return back()->withErrors(['avatar' => 'アップロードする画像を選択してください。']);
        }

        $oldPath = (string)($guardian->avatar_path ?? '');

        if ($remove) {
            $guardian->avatar_path = null;
            $guardian->save();

            if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }

            return redirect()
                ->route('family.profile.edit', ['guardian_id' => $guardianId])
                ->with('success', 'アイコン画像を削除しました。');
        }

        $newPath = $request->file('avatar')->store('family-avatars', 'local');
        $guardian->avatar_path = $newPath;
        $guardian->save();

        if ($oldPath !== '' && $oldPath !== $newPath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()
            ->route('family.profile.edit', ['guardian_id' => $guardianId])
            ->with('success', 'アイコン画像を更新しました。');
    }

    public function avatar(Request $request)
    {
        $fallback = redirect(asset('images/parents.png'));
        if (!Schema::hasColumn('guardians', 'avatar_path')) {
            return $fallback;
        }

        $ctx = FamilyChildContext::resolve($request);
        $childId = (int)$ctx['activeChild']->id;
        if ($childId <= 0) {
            return $fallback;
        }

        $guardian = FamilyGuardianResolver::resolve($request, $childId);
        if (!$guardian) {
            return $fallback;
        }

        $path = trim((string)($guardian->avatar_path ?? ''));
        if ($path === '' || !Storage::disk('local')->exists($path)) {
            return $fallback;
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
