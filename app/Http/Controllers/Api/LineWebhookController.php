<?php

namespace App\Http\Controllers\Api;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\FamilyMessage;
use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\GuardianLineLinkToken;
use App\Services\Line\LineApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class LineWebhookController extends Controller
{
    public function handle(Request $request, LineApiService $lineApi): JsonResponse
    {
        $rawBody = (string) $request->getContent();
        $signature = (string) $request->header('X-Line-Signature', '');

        if (!$lineApi->verifyWebhookSignature($rawBody, $signature)) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            $events = (array) ($payload['events'] ?? []);

            foreach ($events as $event) {
                try {
                    $this->handleEvent($request, (array) $event, $lineApi);
                } catch (\Throwable $e) {
                    Log::warning('LINE webhook event handling failed', [
                        'event_type' => (string) data_get($event, 'type', ''),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('LINE webhook parsing failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function handleEvent(Request $request, array $event, LineApiService $lineApi): void
    {
        if ((string) data_get($event, 'type') !== 'message') {
            return;
        }
        if ((string) data_get($event, 'message.type') !== 'text') {
            return;
        }

        $lineUserId = trim((string) data_get($event, 'source.userId', ''));
        $replyToken = trim((string) data_get($event, 'replyToken', ''));
        $rawText = trim((string) data_get($event, 'message.text', ''));

        if ($lineUserId === '' || $replyToken === '') {
            return;
        }

        $linkedGuardian = Guardian::query()->where('line_user_id', $lineUserId)->first();
        if ($linkedGuardian) {
            $this->storeInboundMessage($linkedGuardian, $rawText, (string) data_get($event, 'message.id', ''));
            return;
        }

        $normalizedToken = $this->normalizeToken($rawText);
        if ($normalizedToken === null) {
            $this->safeReply($lineApi, $replyToken, 'まずPLDLでLINE連携コードを発行し、6〜8桁のコードをこのLINEに送信してください。');
            return;
        }

        if ($this->isRateLimited($request, $lineUserId)) {
            $this->safeReply($lineApi, $replyToken, '試行回数が多すぎます。1分ほど時間をおいて再度お試しください。');
            return;
        }

        $result = $this->consumeLinkToken($normalizedToken, $lineUserId);
        if ($result === 'success') {
            $this->safeReply($lineApi, $replyToken, '連携が完了しました。以後このLINEで連絡できます。');
            return;
        }

        if ($result === 'expired') {
            $this->safeReply($lineApi, $replyToken, 'コードの有効期限が切れています。PLDLで再発行してください。');
            return;
        }

        if ($result === 'line_user_conflict') {
            $this->safeReply($lineApi, $replyToken, 'このLINEアカウントはすでに他の保護者に連携されています。');
            return;
        }

        $this->safeReply($lineApi, $replyToken, 'コードが一致しません。PLDLで最新のコードを発行して再度お試しください。');
    }

    private function normalizeToken(string $text): ?string
    {
        $normalized = mb_convert_kana($text, 'n', 'UTF-8');
        $numeric = preg_replace('/\D+/', '', $normalized);

        if (!is_string($numeric) || !preg_match('/^\d{6,8}$/', $numeric)) {
            return null;
        }

        return $numeric;
    }

    private function isRateLimited(Request $request, string $lineUserId): bool
    {
        $ipKey = 'line-link-code:ip:'.sha1((string) $request->ip());
        $userKey = 'line-link-code:user:'.sha1($lineUserId);

        if (RateLimiter::tooManyAttempts($ipKey, 30) || RateLimiter::tooManyAttempts($userKey, 10)) {
            return true;
        }

        RateLimiter::hit($ipKey, 60);
        RateLimiter::hit($userKey, 60);

        return false;
    }

    private function consumeLinkToken(string $plainToken, string $lineUserId): string
    {
        $tokenHash = GuardianLineLinkToken::hashToken($plainToken);
        $token = GuardianLineLinkToken::query()
            ->where('token_hash', $tokenHash)
            ->latest('id')
            ->first();

        if (!$token) {
            return 'invalid';
        }

        try {
            return DB::transaction(function () use ($token, $lineUserId, $tokenHash) {
                $lockedToken = GuardianLineLinkToken::query()
                    ->whereKey($token->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedToken || $lockedToken->token_hash !== $tokenHash || $lockedToken->consumed_at !== null) {
                    return 'invalid';
                }

                if ($lockedToken->expires_at->isPast()) {
                    $lockedToken->consumed_at = now();
                    $lockedToken->save();
                    return 'expired';
                }

                $guardian = Guardian::query()->lockForUpdate()->find($lockedToken->guardian_id);
                if (!$guardian) {
                    $lockedToken->consumed_at = now();
                    $lockedToken->save();
                    return 'invalid';
                }

                $existsOther = Guardian::query()
                    ->where('line_user_id', $lineUserId)
                    ->whereKeyNot($guardian->id)
                    ->exists();
                if ($existsOther) {
                    return 'line_user_conflict';
                }

                if (!empty($guardian->line_user_id) && (string) $guardian->line_user_id !== $lineUserId) {
                    return 'line_user_conflict';
                }

                $guardian->line_user_id = $lineUserId;
                if (empty($guardian->preferred_contact)) {
                    $guardian->preferred_contact = 'line';
                }
                $guardian->save();

                $lockedToken->consumed_at = now();
                $lockedToken->save();

                GuardianLineLinkToken::query()
                    ->where('guardian_id', $guardian->id)
                    ->whereNull('consumed_at')
                    ->whereKeyNot($lockedToken->id)
                    ->update(['consumed_at' => now()]);

                return 'success';
            });
        } catch (\Throwable $e) {
            Log::warning('LINE code link failed', [
                'line_user_id' => $lineUserId,
                'token_id' => (int) $token->id,
                'error' => $e->getMessage(),
            ]);

            return 'invalid';
        }
    }

    private function safeReply(LineApiService $lineApi, string $replyToken, string $text): void
    {
        try {
            $lineApi->replyText($replyToken, $text);
        } catch (\Throwable $e) {
            Log::warning('LINE reply failed', ['error' => $e->getMessage()]);
        }
    }

    private function storeInboundMessage(Guardian $guardian, string $text, string $lineMessageId): void
    {
        DB::transaction(function () use ($guardian, $text, $lineMessageId) {
            $thread = ChatThread::query()->firstOrCreate(
                ['guardian_id' => $guardian->id],
                ['status' => 'open']
            );

            if ($lineMessageId !== '') {
                $alreadyExists = ChatMessage::query()
                    ->where('line_message_id', $lineMessageId)
                    ->exists();
                if ($alreadyExists) {
                    return;
                }
            }

            ChatMessage::query()->create([
                'thread_id' => $thread->id,
                'sender_type' => 'family',
                'sender_id' => null,
                'body' => $text,
                'line_message_id' => $lineMessageId !== '' ? $lineMessageId : null,
                'delivery_status' => 'sent',
            ]);

            $this->syncToFamilyMessages((int) $guardian->id, $text);

            $thread->last_message_at = now();
            $thread->status = 'open';
            if (Schema::hasTable('chat_threads') && Schema::hasColumn('chat_threads', 'unread_count_staff')) {
                $thread->unread_count_staff = (int) ($thread->unread_count_staff ?? 0) + 1;
            }
            $thread->save();
        });
    }

    private function syncToFamilyMessages(int $guardianId, string $body): void
    {
        if ($guardianId <= 0 || trim($body) === '') {
            return;
        }
        if (!Schema::hasTable('family_messages') || !Schema::hasColumn('family_messages', 'child_id')) {
            return;
        }

        $childId = (int) DB::table('child_guardian')
            ->where('guardian_id', $guardianId)
            ->orderBy('child_id')
            ->value('child_id');

        if ($childId <= 0) {
            return;
        }

        $payload = [
            'child_id' => $childId,
            'title' => null,
            'body' => $body,
        ];
        if (Schema::hasColumn('family_messages', 'sender_type')) {
            $payload['sender_type'] = 'family';
        }

        try {
            FamilyMessage::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to sync inbound LINE message to family_messages', [
                'guardian_id' => $guardianId,
                'child_id' => $childId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
