<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\GuardianLineLinkToken;
use App\Services\Line\LineApiService;
use App\Support\FamilyGuardianResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FamilyLineLinkController extends Controller
{
    public function redirectToLine(Request $request, LineApiService $lineApi): RedirectResponse
    {
        $guardian = $this->resolveTargetGuardian($request);
        if (!$lineApi->isLoginConfigured()) {
            return $this->toProfile($guardian, 'LINE連携設定が不足しています。管理者へお問い合わせください。');
        }

        $state = Str::random(48);
        $nonce = Str::random(48);
        $request->session()->put('family_line_oauth', [
            'guardian_id' => (int) $guardian->id,
            'state' => $state,
            'nonce' => $nonce,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        try {
            return redirect()->away($lineApi->buildLoginAuthorizeUrl($state, $nonce));
        } catch (\Throwable $e) {
            Log::warning('Failed to build LINE login url', [
                'guardian_id' => (int) $guardian->id,
                'error' => $e->getMessage(),
            ]);

            return $this->toProfile($guardian, 'LINE連携の開始に失敗しました。時間をおいて再度お試しください。');
        }
    }

    public function callback(Request $request, LineApiService $lineApi): RedirectResponse
    {
        $oauth = (array) $request->session()->pull('family_line_oauth', []);
        $guardianId = (int) ($oauth['guardian_id'] ?? 0);

        if ((string) $request->query('error', '') !== '') {
            return $this->toProfileById($guardianId, 'LINE連携がキャンセルされました。');
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($guardianId <= 0 || empty($oauth['state']) || empty($oauth['nonce']) || $code === '') {
            return $this->toProfileById($guardianId, 'LINE連携情報が無効です。再度お試しください。');
        }

        $expiresAt = (int) ($oauth['expires_at'] ?? 0);
        if ($expiresAt <= 0 || now()->timestamp > $expiresAt) {
            return $this->toProfileById($guardianId, 'LINE連携の有効期限が切れました。もう一度実行してください。');
        }

        if (!hash_equals((string) $oauth['state'], $state)) {
            return $this->toProfileById($guardianId, 'LINE連携の検証に失敗しました。再度お試しください。');
        }

        try {
            $this->assertGuardianIsAllowed($request, $guardianId);

            $tokenPayload = $lineApi->exchangeAuthorizationCode($code);
            $idToken = trim((string) ($tokenPayload['id_token'] ?? ''));
            if ($idToken === '') {
                throw new \RuntimeException('id_token missing');
            }

            $verified = $lineApi->verifyIdToken($idToken);
            $sub = trim((string) ($verified['sub'] ?? ''));
            $nonce = trim((string) ($verified['nonce'] ?? ''));

            if ($sub === '' || $nonce === '' || !hash_equals((string) $oauth['nonce'], $nonce)) {
                throw new \RuntimeException('nonce mismatch');
            }

            DB::transaction(function () use ($guardianId, $sub) {
                $guardian = Guardian::query()->lockForUpdate()->findOrFail($guardianId);

                $existsOther = Guardian::query()
                    ->where('line_user_id', $sub)
                    ->whereKeyNot($guardian->id)
                    ->exists();
                if ($existsOther) {
                    throw new \RuntimeException('already linked to another guardian');
                }

                $guardian->line_user_id = $sub;
                if (empty($guardian->preferred_contact)) {
                    $guardian->preferred_contact = 'line';
                }
                $guardian->save();

                GuardianLineLinkToken::query()
                    ->where('guardian_id', $guardian->id)
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);
            });

            return redirect()
                ->route('family.profile.edit', ['guardian_id' => $guardianId])
                ->with('success', 'LINE連携が完了しました。');
        } catch (\Throwable $e) {
            Log::warning('Family LINE callback failed', [
                'guardian_id' => $guardianId,
                'error' => $e->getMessage(),
            ]);

            $message = str_contains($e->getMessage(), 'already linked')
                ? 'このLINEアカウントは他の保護者に連携済みです。'
                : 'LINE連携に失敗しました。再度お試しください。';

            return $this->toProfileById($guardianId, $message);
        }
    }

    public function createLinkToken(Request $request): RedirectResponse
    {
        $guardian = $this->resolveTargetGuardian($request, true);
        if ((string) $guardian->line_user_id !== '') {
            return $this->toProfile($guardian, 'すでにLINE連携済みです。解除が必要な場合は管理者へご連絡ください。');
        }

        $plainToken = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        DB::transaction(function () use ($guardian, $plainToken, $expiresAt) {
            GuardianLineLinkToken::query()
                ->where('guardian_id', $guardian->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            GuardianLineLinkToken::query()->create([
                'guardian_id' => $guardian->id,
                'token_hash' => GuardianLineLinkToken::hashToken($plainToken),
                'expires_at' => $expiresAt,
            ]);
        });

        return redirect()
            ->route('family.profile.edit', ['guardian_id' => $guardian->id])
            ->with('line_link_code', $plainToken)
            ->with('line_link_code_expires_at', $expiresAt->format('H:i'))
            ->with('success', 'LINE連携コードを発行しました。5分以内にLINEで送信してください。');
    }

    private function resolveTargetGuardian(Request $request, bool $strictInput = false): Guardian
    {
        $childId = (int) $request->session()->get('family_child_id', 0);
        abort_unless($childId > 0, 403);

        $child = Child::query()->with('guardians')->findOrFail($childId);
        $guardians = $child->guardians->values();
        abort_unless($guardians->isNotEmpty(), 403);

        $requestedGuardianId = (int) $request->input('guardian_id', $strictInput ? 0 : (int) $request->query('guardian_id', 0));
        $targetGuardian = $requestedGuardianId > 0
            ? $guardians->firstWhere('id', $requestedGuardianId)
            : null;

        if (!$targetGuardian) {
            $targetGuardian = FamilyGuardianResolver::resolve($request, $childId) ?? $guardians->first();
        }

        abort_if(!$targetGuardian, 403);
        FamilyGuardianResolver::setForChild($request, $childId, (int) $targetGuardian->id);

        return $targetGuardian;
    }

    private function assertGuardianIsAllowed(Request $request, int $guardianId): void
    {
        $childId = (int) $request->session()->get('family_child_id', 0);
        abort_unless($childId > 0 && $guardianId > 0, 403);

        $child = Child::query()->with('guardians:id')->findOrFail($childId);
        $allowed = $child->guardians->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(in_array($guardianId, $allowed, true), 403);
    }

    private function toProfile(Guardian $guardian, string $message): RedirectResponse
    {
        return redirect()
            ->route('family.profile.edit', ['guardian_id' => $guardian->id])
            ->with('line_link_error', $message);
    }

    private function toProfileById(int $guardianId, string $message): RedirectResponse
    {
        $params = $guardianId > 0 ? ['guardian_id' => $guardianId] : [];

        return redirect()
            ->route('family.profile.edit', $params)
            ->with('line_link_error', $message);
    }
}
