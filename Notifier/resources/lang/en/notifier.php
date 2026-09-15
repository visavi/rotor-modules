<?php

return [
    'notifier' => 'Message notifications',
    'settings' => 'Notification settings',

    'active'      => 'Check for new messages in background',
    'active_help' => 'While the tab is open, the engine asks the server about new messages and updates the counter in the header. When off, the counter changes only on page reload, and sound and notifications do not work',

    'interval'         => 'Polling interval',
    'interval_custom'  => 'Custom interval (sec.)',
    'interval_help'    => 'How often to ask the server. Only one tab polls, the others receive the result from it, so extra tabs add no load',
    'interval_invalid' => 'Polling interval must be between :min and :max seconds',

    'notify_group' => 'How to announce a new message',
    'notify_help'  => 'Combine any of them or enable none — then a new message only shows up as a number in the header',

    'sound'         => 'Notification sound',
    'sound_none'    => 'No sound',
    'sound_test'    => 'Play',
    'sound_help'    => 'The browser allows sound only after the first click on the site. Your own file can be placed in modules/Notifier/resources/assets/sounds',
    'sound_invalid' => 'Selected sound does not exist',

    'volume'         => 'Volume',
    'volume_invalid' => 'Volume must be between 0 and 100',

    'title'        => 'Mark the message count in the tab title',
    'title_help'   => 'The tab title becomes “(2) Site name” — visible even while the tab is inactive',
    'desktop'      => 'Show browser notifications',
    'desktop_help' => 'An operating system window on top of other programs — visible even when the site is minimised. Shown only while the tab is inactive. The browser asks for permission on the first click on the site, works over https only',

    'desktop_title' => 'New private message',
    'desktop_body'  => 'Unread messages: :count',

    'sound_ding'   => 'Ding',
    'sound_chime'  => 'Chime',
    'sound_bell'   => 'Bell',
    'sound_pop'    => 'Pop',
    'sound_soft'   => 'Soft',
    'sound_double' => 'Two notes',

    'seconds' => 'sec.',
];
