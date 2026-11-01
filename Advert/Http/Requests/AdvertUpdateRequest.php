<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Requests;

use Illuminate\Support\Arr;
use Modules\Advert\Models\Advert;

/**
 * Правка ссылки владельцем: меняются адрес и название,
 * опции оплачены при покупке — жирность не меняется, цвет сменить можно только оплаченный
 */
class AdvertUpdateRequest extends AdvertRequest
{
    /**
     * Поля для записи в ссылку. Пустой цвет не стирает оплаченный
     */
    public function fieldsFor(Advert $link): array
    {
        $fields = Arr::only($this->advertFields(), ['site', 'name', 'color']);

        if (! $link->color || $fields['color'] === null) {
            unset($fields['color']);
        }

        return $fields;
    }
}
