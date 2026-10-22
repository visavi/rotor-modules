<?php

declare(strict_types=1);

namespace Modules\Lottery\Http\Requests;

use Illuminate\Foundation\Http\Attributes\RedirectTo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Покупка билета: число из диапазона тиража
 */
#[RedirectTo('lottery')]
class TicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'between:' . (int) setting('lottery_min') . ',' . (int) setting('lottery_max')],
        ];
    }

    public function messages(): array
    {
        return [
            'number' => __('lottery::lottery.must_enter_number'),
        ];
    }
}
