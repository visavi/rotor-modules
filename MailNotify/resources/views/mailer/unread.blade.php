<x-mail.layout :subject="$subject" :preheader="__('mail_notify::mail_notify.unread_preheader')">
    <x-mail.heading>{{ __('mailer.hello', ['username' => $username]) }}</x-mail.heading>

    <x-mail.text>{{ __('mail_notify::mail_notify.unread_intro', ['site' => setting('title'), 'count' => $count]) }}</x-mail.text>

    <x-mail.button :url="route('messages.index')">{{ __('mail_notify::mail_notify.read_messages') }}</x-mail.button>

    <x-mail.text muted>
        {{ __('mail_notify::mail_notify.unsubscribe_hint') }}
        <x-mail.link :url="route('mail-notify.unsubscribe', ['key' => $unsubscribe])">{{ __('mail_notify::mail_notify.unsubscribe') }}</x-mail.link>
    </x-mail.text>

    <x-slot:subcopy>
        {{ __('mailer.follow_link') }}:<br>
        <x-mail.link :url="route('messages.index')">{{ route('messages.index') }}</x-mail.link>
    </x-slot:subcopy>
</x-mail.layout>
