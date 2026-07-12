<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

use Illuminate\Http\Client\Response;

class YandexProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'yandex';
    }

    protected function getAuthUrl(string $state): string
    {
        return 'https://oauth.yandex.ru/authorize?' . http_build_query([
            'client_id'     => setting('social_yandex_client_id'),
            'response_type' => 'code',
            'state'         => $state,
        ]);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.yandex.ru/token';
    }

    protected function getTokenParams(string $code): array
    {
        return [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => setting('social_yandex_client_id'),
            'client_secret' => setting('social_yandex_client_secret'),
        ];
    }

    protected function getUserUrl(): string
    {
        return 'https://login.yandex.ru/info?format=json';
    }

    protected function fetchUser(string $token): Response
    {
        return $this->http()->withHeaders(['Authorization' => 'OAuth ' . $token])
            ->get($this->getUserUrl());
    }

    protected function mapUser(array $data): array
    {
        return [
            'id'    => (string) $data['id'],
            'email' => $data['default_email'] ?? null,
            'name'  => $data['login'] ?? $data['display_name'] ?? null,
        ];
    }
}
