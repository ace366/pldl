<?php

namespace App\Services\Line;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LineApiService
{
    public function isLoginConfigured(): bool
    {
        return $this->loginChannelId() !== ''
            && $this->loginChannelSecret() !== ''
            && $this->loginRedirectUri() !== '';
    }

    public function isMessagingConfigured(): bool
    {
        return $this->messagingChannelAccessToken() !== ''
            && $this->messagingChannelSecret() !== '';
    }

    public function buildLoginAuthorizeUrl(string $state, string $nonce): string
    {
        if (!$this->isLoginConfigured()) {
            throw new RuntimeException('LINE Login の設定が不足しています。');
        }

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->loginChannelId(),
            'redirect_uri' => $this->loginRedirectUri(),
            'state' => $state,
            'scope' => 'openid profile',
            'nonce' => $nonce,
            'prompt' => 'consent',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://access.line.me/oauth2/v2.1/authorize?'.$query;
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code): array
    {
        if (!$this->isLoginConfigured()) {
            throw new RuntimeException('LINE Login の設定が不足しています。');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->loginRedirectUri(),
                'client_id' => $this->loginChannelId(),
                'client_secret' => $this->loginChannelSecret(),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LINE token 取得に失敗しました。');
        }

        return (array) $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyIdToken(string $idToken): array
    {
        if (!$this->isLoginConfigured()) {
            throw new RuntimeException('LINE Login の設定が不足しています。');
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => $this->loginChannelId(),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LINE id_token 検証に失敗しました。');
        }

        return (array) $response->json();
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = $this->messagingChannelSecret();
        if ($secret === '' || empty($signature)) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, (string) $signature);
    }

    public function replyText(string $replyToken, string $text): void
    {
        if (!$this->isMessagingConfigured()) {
            throw new RuntimeException('LINE Messaging API の設定が不足しています。');
        }

        $response = Http::withToken($this->messagingChannelAccessToken())
            ->timeout(10)
            ->post('https://api.line.me/v2/bot/message/reply', [
                'replyToken' => $replyToken,
                'messages' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LINE replyMessage 送信に失敗しました。');
        }
    }

    public function pushText(string $lineUserId, string $text): void
    {
        if (!$this->isMessagingConfigured()) {
            throw new RuntimeException('LINE Messaging API の設定が不足しています。');
        }

        $response = Http::withToken($this->messagingChannelAccessToken())
            ->timeout(10)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LINE pushMessage 送信に失敗しました。');
        }
    }

    private function loginChannelId(): string
    {
        return trim((string) config('services.line.login_channel_id', ''));
    }

    private function loginChannelSecret(): string
    {
        return trim((string) config('services.line.login_channel_secret', ''));
    }

    private function loginRedirectUri(): string
    {
        return trim((string) config('services.line.login_redirect_uri', ''));
    }

    private function messagingChannelAccessToken(): string
    {
        return trim((string) config('services.line.messaging_channel_access_token', ''));
    }

    private function messagingChannelSecret(): string
    {
        return trim((string) config('services.line.channel_secret', ''));
    }
}
