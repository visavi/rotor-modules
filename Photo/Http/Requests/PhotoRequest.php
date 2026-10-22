<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Фото — правка на сайте, в админке и в API
 */
class PhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:' . setting('photo_title_min'), 'max:' . setting('photo_title_max')],
            // Описание необязательно: снимок и есть содержимое записи
            'text'   => ['nullable', 'string', new TextLengthRule((int) setting('photo_text_min'), (int) setting('photo_text_max'))],
            'closed' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            // Не сообщение на всё поле: оно перекрыло бы текст антифлуда
            'title.required' => __('validator.text'),
            'title.string'   => __('validator.text'),
            'title.min'      => __('validator.text'),
            'title.max'      => __('validator.text'),
        ];
    }

    /**
     * Поля фото из запроса
     */
    public function photoFields(): array
    {
        return [
            'title'  => $this->string('title')->value(),
            'text'   => $this->string('text')->value(),
            'closed' => $this->boolean('closed'),
        ];
    }
}
