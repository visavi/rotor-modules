<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Http\Requests;

use App\Models\BlackList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CompleteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,filter',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $email = Str::lower($value);
                    $domain = Str::afterLast($email, '@');

                    if (BlackList::isBlacklisted('email', $email)) {
                        $fail(__('users.email_is_blacklisted'));
                    } elseif (BlackList::isBlacklisted('domain', $domain)) {
                        $fail(__('users.domain_is_blacklisted'));
                    } elseif (\App\Models\User::query()->where('email', $email)->exists()) {
                        // Адрес введён руками и никем не подтверждён: привязка к чужому
                        // аккаунту была бы захватом, поэтому только отказ
                        $fail(__('social_auth::social_auth.email_already_exists'));
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('users.email'),
        ];
    }

    public function getRedirectUrl(): string
    {
        return route('social.complete');
    }
}
