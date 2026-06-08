<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractOAuthProvider
{
    /**
     * Идентификатор провайдера (google, github, yandex, vk)
     */
    abstract public function getName(): string;

    /**
     * URL для редиректа пользователя на страницу авторизации провайдера
     */
    abstract protected function getAuthUrl(string $state): string;

    /**
     * URL для обмена кода на токен
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Параметры POST-запроса для получения токена
     */
    abstract protected function getTokenParams(string $code): array;

    /**
     * URL для получения данных пользователя
     */
    abstract protected function getUserUrl(): string;

    /**
     * Маппинг ответа провайдера в единый формат
     *
     * @return array{id: string, email: string|null, name: string|null}
     */
    abstract protected function mapUser(array $data): array;

    /**
     * Callback URL для регистрации в настройках приложения провайдера
     */
    public function getRedirectUrl(): string
    {
        return route('social.callback', ['provider' => $this->getName()]);
    }

    /**
     * Строит полный URL авторизации с CSRF state
     */
    public function buildAuthUrl(string $state): string
    {
        return $this->getAuthUrl($state);
    }

    /**
     * Обменивает код авторизации на access token
     */
    public function getToken(string $code): string
    {
        $response = Http::asForm()
            ->post($this->getTokenUrl(), $this->getTokenParams($code));

        if ($response->failed()) {
            throw new RuntimeException('OAuth token request failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['access_token'] ?? throw new RuntimeException('No access_token in response');
    }

    /**
     * Получает данные пользователя по токену
     *
     * @return array{id: string, email: string|null, name: string|null}
     */
    public function getUser(string $token): array
    {
        $response = $this->fetchUser($token);

        if ($response->failed()) {
            throw new RuntimeException('OAuth user request failed: ' . $response->body());
        }

        return $this->mapUser($response->json());
    }

    /**
     * HTTP-запрос к API провайдера за данными пользователя
     */
    protected function fetchUser(string $token): Response
    {
        return Http::withToken($token)->get($this->getUserUrl());
    }
}
