<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

class GoogleProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'google';
    }

    protected function getAuthUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => setting('social_google_client_id'),
            'redirect_uri'  => $this->getRedirectUrl(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ]);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function getTokenParams(string $code): array
    {
        return [
            'client_id'     => setting('social_google_client_id'),
            'client_secret' => setting('social_google_client_secret'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->getRedirectUrl(),
        ];
    }

    protected function getUserUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v2/userinfo';
    }

    protected function mapUser(array $data): array
    {
        return [
            'id'    => (string) $data['id'],
            'email' => $data['email'] ?? null,
            'name'  => $data['name'] ?? null,
        ];
    }
}
