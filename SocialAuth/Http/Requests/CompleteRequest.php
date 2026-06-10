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
                'email',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $email = strtolower($value);
                    $domain = Str::substr(strrchr($email, '@'), 1);

                    if (BlackList::isBlacklisted('email', $email)) {
                        $fail(__('users.email_is_blacklisted'));
                    } elseif (BlackList::isBlacklisted('domain', $domain)) {
                        $fail(__('users.domain_is_blacklisted'));
                    } elseif (! setting('social_autolink_email')) {
                        if (\App\Models\User::query()->where('email', $email)->exists()) {
                            $fail(__('social_auth::social_auth.email_already_exists'));
                        }
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
