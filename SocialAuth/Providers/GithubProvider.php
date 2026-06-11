<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GithubProvider extends AbstractOAuthProvider
{
    public function getName(): string
    {
        return 'github';
    }

    protected function getAuthUrl(string $state): string
    {
        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => setting('social_github_client_id'),
            'scope'     => 'read:user user:email',
            'state'     => $state,
        ]);
    }

    protected function getTokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function getTokenParams(string $code): array
    {
        return [
            'client_id'     => setting('social_github_client_id'),
            'client_secret' => setting('social_github_client_secret'),
            'code'          => $code,
        ];
    }

    public function getToken(string $code): string
    {
        $response = Http::asForm()
            ->withHeaders(['Accept' => 'application/json'])
            ->post($this->getTokenUrl(), $this->getTokenParams($code));

        if ($response->failed()) {
            throw new RuntimeException('GitHub token request failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['access_token'] ?? throw new RuntimeException('No access_token in response');
    }

    protected function getUserUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function fetchUser(string $token): Response
    {
        return Http::withToken($token)
            ->withHeaders([
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => config('app.name'),
            ])
            ->get($this->getUserUrl());
    }

    protected function mapUser(array $data): array
    {
        return [
            'id'    => (string) $data['id'],
            'email' => $data['email'] ?? null,
            'name'  => $data['login'] ?? $data['name'] ?? null,
        ];
    }

    public function getUser(string $token): array
    {
        $user = parent::getUser($token);

        if (empty($user['email'])) {
            $user['email'] = $this->fetchVerifiedEmail($token);
        }

        return $user;
    }

    private function fetchVerifiedEmail(string $token): ?string
    {
        $response = Http::withToken($token)
            ->withHeaders([
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => config('app.name'),
            ])
            ->get('https://api.github.com/user/emails');

        if ($response->failed()) {
            return null;
        }

        foreach ($response->json() as $email) {
            if (($email['primary'] ?? false) && ($email['verified'] ?? false)) {
                return $email['email'];
            }
        }

        return null;
    }
}
