<x-mail.layout :subject="$subject" :preheader="__('mail_notify::mail_notify.birthday_preheader')">
    <x-mail.heading>{{ __('mailer.hello', ['username' => $username]) }}</x-mail.heading>

    <x-mail.text>{{ __('mail_notify::mail_notify.birthday_text') }}</x-mail.text>

    <x-mail.text>{{ __('mail_notify::mail_notify.site_administration', ['site' => setting('title')]) }}</x-mail.text>

    <x-mail.text muted>
        {{ __('mail_notify::mail_notify.unsubscribe_hint') }}
        <x-mail.link :url="route('mail-notify.unsubscribe', ['key' => $unsubscribe])">{{ __('mail_notify::mail_notify.unsubscribe') }}</x-mail.link>
    </x-mail.text>
</x-mail.layout>
