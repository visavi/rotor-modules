<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Requests;

use App\Rules\TextLengthRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Offer\Models\Offer;

/**
 * Официальный ответ администрации на запись
 */
class OfferReplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reply'  => ['required', 'string', new TextLengthRule((int) setting('offer_reply_min'), (int) setting('offer_reply_max'))],
            'status' => ['required', Rule::in(Offer::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => __('validator.text'),
            'reply.string'   => __('validator.text'),
            'status.*'       => __('offer::offers.status_invalid'),
        ];
    }
}
