<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Requests;

use App\Models\User;
use App\Rules\TextLengthRule;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Gift\Models\Gift;

/**
 * Отправка подарка — общая для сайта и API
 */
class GiftSendRequest extends FormRequest
{
    private Gift $gift;

    /** @var Collection<int, User> */
    private Collection $recipients;

    public function rules(): array
    {
        $maxUsers = (int) setting('gift_max_users');

        return [
            'users' => [
                'bail',
                'required',
                'array',
                'max:' . $maxUsers,
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->recipients = User::query()->whereIn('login', $value)->get();

                    if ($this->recipients->count() !== count($value)) {
                        $fail(__('gift::gifts.users_not_found'));
                    }
                },
            ],
            'text' => ['nullable', 'string', new TextLengthRule(0, 1000)],
        ];
    }

    public function messages(): array
    {
        return [
            'users.required' => __('validator.user'),
            'users.array'    => __('validator.user'),
            'users.max'      => __('gift::gifts.max_users', ['max' => setting('gift_max_users')]),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // Стоимость списывается за каждого получателя
                if (getUser('money') < $this->total()) {
                    $validator->errors()->add('money', __('gift::gifts.money_not_enough'));
                }
            },
        ];
    }

    /**
     * Получатели, найденные при проверке логинов
     *
     * @return Collection<int, User>
     */
    public function recipients(): Collection
    {
        return $this->recipients;
    }

    /**
     * Стоимость подарка за всех получателей
     */
    public function total(): int
    {
        return $this->gift->price * $this->recipients->count();
    }

    /**
     * Подарок из адреса запроса
     */
    public function gift(): Gift
    {
        return $this->gift;
    }

    protected function prepareForValidation(): void
    {
        $gift = Gift::query()->find(int($this->route('id')));

        if (! $gift) {
            abort(404, __('gift::gifts.gift_not_found'));
        }

        $this->gift = $gift;

        // Повторный логин — тот же получатель, подарок ему уходит один раз
        $users = $this->input('users');

        if (is_array($users)) {
            $this->merge(['users' => array_values(array_unique(array_filter($users, 'is_string')))]);
        }
    }
}
