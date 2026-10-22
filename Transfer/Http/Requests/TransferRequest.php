<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Requests;

use App\Models\User;
use App\Rules\TextLengthRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Перевод денег — общий для сайта и API
 */
class TransferRequest extends FormRequest
{
    private ?User $recipient = null;

    public function rules(): array
    {
        $user = getUser();

        return [
            'user' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    $this->recipient = getUserByLogin($value);

                    if (! $this->recipient) {
                        $fail(__('validator.user'));
                    } elseif ($this->recipient->id === $user->id) {
                        $fail(__('transfer::transfers.transfer_yourself'));
                    }
                },
            ],
            'money' => [
                'bail',
                // Переводы открыты не всем: нужен порог баллов
                static function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if ($user->point < setting('sendmoneypoint')) {
                        $fail(__('transfer::transfers.transfer_point', [
                            'point' => plural(setting('sendmoneypoint'), setting('scorename')),
                        ]));
                    }
                },
                'required',
                'integer',
                'min:1',
                'max:' . $user->money,
            ],
            'text' => ['nullable', 'string', new TextLengthRule(0, (int) setting('comment_text_max'))],
        ];
    }

    public function messages(): array
    {
        return [
            'money.required' => __('transfer::transfers.transfer_wrong_amount'),
            'money.integer'  => __('transfer::transfers.transfer_wrong_amount'),
            'money.min'      => __('transfer::transfers.transfer_wrong_amount'),
            'money.max'      => __('transfer::transfers.transfer_not_money'),
        ];
    }

    /**
     * Получатель, найденный при проверке логина
     */
    public function recipient(): User
    {
        return $this->recipient;
    }
}
