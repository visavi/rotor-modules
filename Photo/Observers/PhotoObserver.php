<?php

declare(strict_types=1);

namespace Modules\Photo\Observers;

use Modules\Photo\Models\Photo;

class PhotoObserver
{
    /**
     * Handle the Photo "created" event.
     */
    public function created(Photo $photo): void
    {
        // Настройки может не быть — модуль ставят раньше, чем задают его настройки
        $photo->user->increment('point', (int) setting('photo_point'));
        $photo->user->increment('money', (int) setting('photo_money'));
    }

    /**
     * Handle the Photo "deleted" event.
     *
     * Своё фото автор удаляет сам — без списания загрузка и удаление по кругу копили бы баллы
     */
    public function deleted(Photo $photo): void
    {
        $user = $photo->user;

        $user->decrement('point', min((int) setting('photo_point'), $user->point));
        $user->decrement('money', min((int) setting('photo_money'), $user->money));
    }
}
