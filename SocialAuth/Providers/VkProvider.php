<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VkProvider extends AbstractOAuthProvider
{
    private const API_VERSION = '5.131';

    public function getName(): string
    {
        return 'vk';
    }

    protected function getAuthUrl(string $state): string
    {
        return 'https://oauth.vk.com/authorize?' . http_build_query([
            'client_id'     => setting('social_vk_client_id'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'scope'         => 'email',
            'response_type' => 'code',
            'state'         => $state,
            'v'             => self::API_VERSION,
        ]);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.vk.com/access_token';
    }

    protected function getTokenParams(string $code): array
    {
        return [
            'client_id'     => setting('social_vk_client_id'),
            'client_secret' => setting('social_vk_client_secret'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'code'          => $code,
        ];
    }

    /**
     * VK возвращает user_id и email прямо в ответе токена — не нужно отдельно запрашивать
     */
    public function getToken(string $code): string
    {
        throw new RuntimeException('Use getTokenData() for VK');
    }

    public function getTokenData(string $code): array
    {
        $response = Http::asForm()->post($this->getTokenUrl(), $this->getTokenParams($code));

        if ($response->failed()) {
            throw new RuntimeException('VK token request failed: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new RuntimeException('VK error: ' . ($data['error_description'] ?? $data['error']));
        }

        return $data;
    }

    protected function getUserUrl(): string
    {
        return 'https://api.vk.com/method/users.get';
    }

    protected function fetchUser(string $token): Response
    {
        return Http::get($this->getUserUrl(), [
            'access_token' => $token,
            'fields'       => 'screen_name',
            'v'            => self::API_VERSION,
        ]);
    }

    protected function mapUser(array $data): array
    {
        $user = $data['response'][0] ?? [];

        return [
            'id'    => (string) ($user['id'] ?? ''),
            'email' => null,
            'name'  => $user['screen_name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: null,
        ];
    }
}
