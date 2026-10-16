{{ __('mailer.hello', ['username' => $username]) }}

{{ __('mail_notify::mail_notify.birthday_text') }}

{{ __('mail_notify::mail_notify.site_administration', ['site' => setting('title')]) }}

{{ __('mail_notify::mail_notify.unsubscribe_hint') }} {{ __('mail_notify::mail_notify.unsubscribe') }}: {{ route('mail-notify.unsubscribe', ['key' => $unsubscribe]) }}

--
{{ setting('copy') }}
{{ config('app.url') }}
