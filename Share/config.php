<?php

return [
    /**
     * Соцсети, доступные для кнопок «Поделиться»
     *
     * Ключ используется в имени настройки: share_<ключ>
     * В link подставляются {url} и {title} записи
     */
    'networks' => [
        'vk' => [
            'name'  => 'VK',
            'icon'  => 'fa-brands fa-vk',
            'color' => '#0077ff',
            'link'  => 'https://vk.com/share.php?url={url}&title={title}',
        ],
        'ok' => [
            'name'  => 'OK',
            'icon'  => 'fa-brands fa-odnoklassniki',
            'color' => '#ee8208',
            'link'  => 'https://connect.ok.ru/offer?url={url}&title={title}',
        ],
        'telegram' => [
            'name'  => 'Telegram',
            'icon'  => 'fa-brands fa-telegram',
            'color' => '#2aabee',
            'link'  => 'https://t.me/share/url?url={url}&text={title}',
        ],
        'whatsapp' => [
            'name'  => 'WhatsApp',
            'icon'  => 'fa-brands fa-whatsapp',
            'color' => '#25d366',
            'link'  => 'https://api.whatsapp.com/send?text={title}%20{url}',
        ],
        'x' => [
            'name'  => 'X',
            'icon'  => 'fa-brands fa-x-twitter',
            // Логотип X одноцветный: наследуем цвет текста, иначе черный пропадет на темной теме
            'color' => 'currentColor',
            'link'  => 'https://x.com/intent/post?url={url}&text={title}',
        ],
        'viber' => [
            'name'  => 'Viber',
            'icon'  => 'fa-brands fa-viber',
            'color' => '#7360f2',
            'link'  => 'viber://forward?text={title}%20{url}',
        ],
        'facebook' => [
            'name'  => 'Facebook',
            'icon'  => 'fa-brands fa-facebook-f',
            'color' => '#0866ff',
            'link'  => 'https://www.facebook.com/sharer/sharer.php?u={url}',
        ],
        'reddit' => [
            'name'  => 'Reddit',
            'icon'  => 'fa-brands fa-reddit-alien',
            'color' => '#ff4500',
            'link'  => 'https://www.reddit.com/submit?url={url}&title={title}',
        ],
        'pinterest' => [
            'name'  => 'Pinterest',
            'icon'  => 'fa-brands fa-pinterest-p',
            'color' => '#e60023',
            'link'  => 'https://pinterest.com/pin/create/button/?url={url}&description={title}',
        ],
        'linkedin' => [
            'name'  => 'LinkedIn',
            'icon'  => 'fa-brands fa-linkedin-in',
            'color' => '#0a66c2',
            'link'  => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
        ],
    ],
];
