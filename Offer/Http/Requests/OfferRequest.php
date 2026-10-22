<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Offer\Models\Offer;

/**
 * Запись раздела предложений — правка на сайте, в админке и в API
 */
class OfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type'  => ['required', Rule::in(Offer::TYPES)],
            'title' => ['required', 'string', 'min:' . setting('offer_title_min'), 'max:' . setting('offer_title_max')],
            'text'  => ['required', 'string', new TextLengthRule((int) setting('offer_text_min'), (int) setting('offer_text_max'))],
        ];
    }

    public function messages(): array
    {
        return [
            'type.*'         => __('offer::offers.type_invalid'),
            'title.required' => __('validator.text'),
            'title.string'   => __('validator.text'),
            'text.required'  => __('validator.text'),
            'text.string'    => __('validator.text'),
        ];
    }
}
