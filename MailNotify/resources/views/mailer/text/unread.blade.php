{{ __('mailer.hello', ['username' => $username]) }}

{{ __('mail_notify::mail_notify.unread_intro', ['site' => setting('title'), 'count' => $count]) }}

{{ __('mail_notify::mail_notify.read_messages') }}: {{ route('messages.index') }}

{{ __('mail_notify::mail_notify.unsubscribe_hint') }} {{ __('mail_notify::mail_notify.unsubscribe') }}: {{ route('mail-notify.unsubscribe', ['key' => $unsubscribe]) }}

--
{{ setting('copy') }}
{{ config('app.url') }}
