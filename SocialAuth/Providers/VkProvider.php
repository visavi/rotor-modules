<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VkProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'vk';
    }

    /**
     * @return array{url: string, code_verifier: string}
     */
    public function buildAuthUrlWithPkce(string $state): array
    {
        $codeVerifier  = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        $url = 'https://id.vk.ru/authorize?' . http_build_query([
            'client_id'             => setting('social_vk_client_id'),
            'redirect_uri'          => $this->getRedirectUrl(),
            'scope'                 => 'email',
            'response_type'         => 'code',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return ['url' => $url, 'code_verifier' => $codeVerifier];
    }

    protected function getAuthUrl(string $state): string
    {
        return $this->buildAuthUrlWithPkce($state)['url'];
    }

    protected function getTokenUrl(): string
    {
        return 'https://id.vk.ru/oauth2/auth';
    }

    protected function getTokenParams(string $code): array
    {
        return [
            'grant_type'    => 'authorization_code',
            'client_id'     => setting('social_vk_client_id'),
            'client_secret' => setting('social_vk_client_secret'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'code'          => $code,
        ];
    }

    public function getTokenData(string $code, string $codeVerifier = '', string $deviceId = ''): array
    {
        $params = $this->getTokenParams($code);

        if ($codeVerifier !== '') {
            $params['code_verifier'] = $codeVerifier;
        }

        if ($deviceId !== '') {
            $params['device_id'] = $deviceId;
        }

        $response = Http::asForm()->post($this->getTokenUrl(), $params);

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
        return 'https://id.vk.ru/oauth2/user_info';
    }

    protected function fetchUser(string $token): Response
    {
        return Http::asForm()->post($this->getUserUrl(), [
            'access_token' => $token,
            'client_id'    => setting('social_vk_client_id'),
        ]);
    }

    protected function mapUser(array $data): array
    {
        $user = $data['user'] ?? [];

        return [
            'id'    => (string) ($user['user_id'] ?? ''),
            'email' => $user['email'] ?? null,
            'name'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: null,
        ];
    }

    private function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function generateCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
