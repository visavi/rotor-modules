<?php

declare(strict_types=1);

namespace Modules\Rating\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Изменение репутации пользователя
 */
class RatingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vote' => ['required', 'in:plus,minus'],
            'text' => ['required', 'string', new TextLengthRule(5, 250)],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => __('validator.text'),
            'text.string'   => __('validator.text'),
        ];
    }

    public function attributes(): array
    {
        return ['vote' => __('main.rating')];
    }

    public function after(): array
    {
        return [
            // Минусовать может только тот, у кого своя репутация положительная
            function (Validator $validator): void {
                if ($this->input('vote') === 'minus' && getUser('rating') < 1) {
                    $validator->errors()->add('vote', __('rating::ratings.reputation_positive'));
                }
            },
        ];
    }
}
