<?php

return [
    'title'    => 'Email mailing',
    'settings' => 'Mailing settings',

    'count_days'      => 'Unread messages email — after how many days without a visit',
    'count_days_help' => 'The email is sent if the user has unread messages and has not visited the site for this many days. One email until the next new message',
    'days_invalid'    => 'Number of days — from 1 to 99!',
    'packet'          => 'Emails per minute',
    'packet_help'     => 'Emails are spread over minutes so as not to hit the mail server limit',
    'packet_invalid'  => 'Emails per minute — from 1 to 999!',

    'receive_newsletters'  => 'Receive newsletters',
    'newsletters_hint'     => 'Receive newsletters from the site by email',
    'token_missing'        => 'Token missing!',
    'token_expired'        => 'The token is already out of date!',
    'success_unsubscribed' => 'You have unsubscribed from the newsletter!',

    'birthday_subject'    => 'Happy birthday from :site',
    'birthday_text'       => 'Happy birthday! We wish you happiness, health, new ideas, creative mood and lots of joy and laughter!',
    'birthday_preheader'  => 'Happy birthday to you',
    'site_administration' => ':site administration',

    'unread_subject'   => 'Unread messages on :site (:count)',
    'unread_intro'     => 'You have unread messages on :site: :count.',
    'unread_preheader' => 'Someone wrote to you — messages are waiting',
    'read_messages'    => 'Read messages',

    'unsubscribe_hint' => "Don't want to receive such emails?",
    'unsubscribe'      => 'Unsubscribe',
];
