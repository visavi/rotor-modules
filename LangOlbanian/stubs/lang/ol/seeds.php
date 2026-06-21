<?php

return [
    'rules' => 'Обще правела для юзераф сайта %SITENAME%',

    'settings' => [
        'language'       => 'ol',
        'currency'       => 'руб',
        'guest_user'     => 'Гозть',
        'deleted_user'   => 'Удалёный аккаунт',
        'default_status' => 'Дух',
        'description'    => 'Краткое апесанее твоево сайта',
        'logos'          => 'Сайт на движке Rotor',
        'moneyname'      => 'манета,манеты,манет',
        'scorename'      => 'балл,балла,баллаф',
    ],

    'statuses' => [
        'novice'       => 'Новечок',
        'local'        => 'Месный',
        'advanced'     => 'Прадвинутый',
        'experienced'  => 'Бывалый',
        'specialist'   => 'Спецеалист',
        'expert'       => 'Знаток',
        'master'       => 'Мастер',
        'professional' => 'Прафессеанал',
        'guru'         => 'Гуру',
        'legend'       => 'Легенда',
    ],

    'notices' => [
        'register_name' => 'Превед при регистрацее ф приват',
        'register_text' => '<p>Дабро пажалаваць, %username%!</p><p>Типерь ты полнаправный юзер сайта, сахране свой логен и пароль ф надёжнам месте, ане пригадяцца тибе для входа на наш сайт.</p><p>Перед пасещением сайта рекамендуем тибе азнакомицца с <a href="/rules">правеламе сайта</a>, это паможит тибе избежаць неприятных ситуацей.</p><p>Жилаем приятно правесте время.</p><p>С уваженеем, администрацея сайта!</p>',

        'down_upload_name' => 'Увидамленее о загрузке файла',
        'down_upload_text' => '<p>Увидамленее о загрузке файла.</p><p>Новый файл <strong>%page%</strong> требуит падтвержденея на публекацею!</p>',

        'down_publish_name' => 'Увидамленее о публекацее файла',
        'down_publish_text' => '<p>Увидамленее о публекацее файла.</p><p>Твой файл <strong>%page%</strong> успешно прашёл праверку и дабавлен ф загрузке</p>',

        'down_unpublish_name' => 'Увидамленее о снятее с публекацее',
        'down_unpublish_text' => '<p>Увидамленее о снятее с публекацее.</p><p>Твой файл <strong>%page%</strong> снят с публекацее из загрузок</p>',

        'down_change_name' => 'Увидамленее об измененее файла',
        'down_change_text' => '<p>Увидамленее об измененее файла.</p><p>Твой файл <strong>%page%</strong> был изменён мадератарам, вазможно ат тибя патребуюцца дапалнительныйе исправленея!</p>',

        'notify_name' => 'Упаминанее юзера',
        'notify_text' => '<p>Юзер %login% упамянул тибя на странеце <strong>%page%</strong></p><p>%text%</p>',

        'comment_reply_name' => 'Атвет на камент',
        'comment_reply_text' => '<p>Юзер %login% атветил на твой камент на странеце <strong>%page%</strong></p><p>%text%</p>',

        'comment_added_name' => 'Новый камент к твоей запесе',
        'comment_added_text' => '<p>Юзер %login% пракаментеравал твою запесь <strong>%page%</strong></p><p>%text%</p>',

        'rating_name' => 'Измененее репутацее',
        'rating_text' => '<p>Юзер %login% паставел тибе %vote%! (Твой рейтенг: %rating%)</p><p>Камент: %comment%</p>',

        'explain_name' => 'Абъясненее нарушенея',
        'explain_text' => '<p>Абъясненее нарушенея: %message%</p>',

        'offer_reply_name' => 'Атвет на праблему / предлаженее',
        'offer_reply_text' => '<p>Увидамленее об атвете на твою праблему / предлаженее</p><p>На твою праблему иле предлаженее <strong>%page%</strong> атветили</p><p>Текст атвета: %text%</p><p>Статус запесе: %status%</p>',

        'article_publish_name' => 'Увидамленее о публекацее статье',
        'article_publish_text' => '<p>Твая статья <strong>%page%</strong> апубликована</p>',

        'article_unpublish_name' => 'Увидамленее о снятее с публекацее',
        'article_unpublish_text' => '<p>Твая статья <strong>%page%</strong> снята с публекацее</p>',
    ],
];
