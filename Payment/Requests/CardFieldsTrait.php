<?php

namespace Modules\Payment\Requests;

/**
 * Поля рекламной карточки, общие для покупки, правки владельцем и админом
 */
trait CardFieldsTrait
{
    /**
     * Правила полей карточки
     */
    protected function cardRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:60'],
            'text'  => ['nullable', 'string', 'max:200'],
            'url'   => ['required', 'url', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500', 'dimensions:min_width=250,min_height=250,max_width=4000,max_height=4000'],
        ];
    }

    /**
     * Названия полей карточки в ошибках
     */
    protected function cardAttributes(): array
    {
        return [
            'title' => __('payment::payments.cards.heading'),
            'text'  => __('payment::payments.cards.text'),
            'url'   => __('payment::payments.cards.url'),
            'image' => __('payment::payments.cards.image'),
        ];
    }
}
