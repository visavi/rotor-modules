<?php

declare(strict_types=1);

namespace Modules\Feedback\Support;

use App\Models\User;

/**
 * Куда уходят обращения: в личку владельцу или письмом
 */
class Feedback
{
    public const string MESSAGES = 'messages';
    public const string EMAIL = 'email';

    public const array DELIVERIES = [self::MESSAGES, self::EMAIL];

    /**
     * Обращения уходят письмом
     */
    public static function byEmail(): bool
    {
        return setting('feedback_delivery') === self::EMAIL;
    }

    /**
     * Адрес для писем: свой из настроек или APP_EMAIL
     */
    public static function recipient(): string
    {
        return (string) (setting('feedback_email') ?: config('app.email'));
    }

    /**
     * Нужно ли спрашивать email в форме
     *
     * Гостю — всегда, иначе ему не ответить. Пользователю — только при письмах
     * и пустом email в профиле: в личке ему отвечают в его диалоге
     */
    public static function asksEmail(?User $user): bool
    {
        return ! $user || (self::byEmail() && ! $user->email);
    }
}
