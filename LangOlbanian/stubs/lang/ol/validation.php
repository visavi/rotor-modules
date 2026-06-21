<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'        => 'Ты должен принять :attribute.',
    'accepted_if'     => 'The :attribute must be accepted when :other is :value.',
    'active_url'      => 'Поле :attribute садержит нидействительный URL.',
    'after'           => 'Ф поле :attribute далжна быць дата болие :date.',
    'after_or_equal'  => 'Ф поле :attribute далжна быць дата болие иле рауняцца :date.',
    'alpha'           => 'Поле :attribute можит садержаць толька буквы.',
    'alpha_dash'      => 'Поле :attribute можит садержаць толька буквы, цифры, дефис и нижнее падчёркеванее.',
    'alpha_num'       => 'Поле :attribute можит садержаць толька буквы и цифры.',
    'array'           => 'Поле :attribute далжно быць массивам.',
    'attached'        => 'Поле :attribute уже прикреплено.',
    'before'          => 'Ф поле :attribute далжна быць дата раньше :date.',
    'before_or_equal' => 'Ф поле :attribute далжна быць дата раньше иле рауняцца :date.',
    'between'         => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць между :min и :max.',
        'file'    => 'Размер файла ф поле :attribute должен быць между :min и :max Килабайт(а).',
        'numeric' => 'Поле :attribute далжно быць между :min и :max.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць между :min и :max.',
    ],
    'boolean'          => 'Поле :attribute далжно имець значенее лагическава типа.',
    'confirmed'        => 'Поле :attribute ни саупадаит с падтвержденеем.',
    'current_password' => 'The password is incorrect.',
    'date'             => 'Поле :attribute ни являецца датай.',
    'date_equals'      => 'Поле :attribute далжно быць датай раунай :date.',
    'date_format'      => 'Поле :attribute ни саатветствуит формату :format.',
    'different'        => 'Поля :attribute и :other далжны разлечацца.',
    'digits'           => 'Длина цифравова поля :attribute далжна быць :digits.',
    'digits_between'   => 'Длина цифравова поля :attribute далжна быць между :min и :max.',
    'dimensions'       => 'Поле :attribute имеит нидапустимыйе размеры избраженея.',
    'distinct'         => 'Поле :attribute садержит паутаряющееся значенее.',
    'email'            => 'Поле :attribute далжно быць действительным электронным адресам.',
    'ends_with'        => 'Поле :attribute далжно заканчевацца аднем из следующех значеней: :values',
    'exists'           => 'Выбраное значенее для :attribute некарректно.',
    'file'             => 'Поле :attribute далжно быць файлам.',
    'filled'           => 'Поле :attribute абязательно для запалненея.',
    'gt'               => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць болие :value.',
        'file'    => 'Размер файла ф поле :attribute должен быць болие :value Килабайт(а).',
        'numeric' => 'Поле :attribute далжно быць болие :value.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць болие :value.',
    ],
    'gte' => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць :value иле болие.',
        'file'    => 'Размер файла ф поле :attribute должен быць :value Килабайт(а) иле болие.',
        'numeric' => 'Поле :attribute далжно быць :value иле болие.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць :value иле болие.',
    ],
    'image'    => 'Поле :attribute далжно быць избраженеем.',
    'in'       => 'Выбраное значенее для :attribute ашибочно.',
    'in_array' => 'Поле :attribute ни сущиствуит ф :other.',
    'integer'  => 'Поле :attribute далжно быць целым числом.',
    'ip'       => 'Поле :attribute далжно быць действительным IP-адресам.',
    'ipv4'     => 'Поле :attribute далжно быць действительным IPv4-адресам.',
    'ipv6'     => 'Поле :attribute далжно быць действительным IPv6-адресам.',
    'json'     => 'Поле :attribute далжно быць JSON строкай.',
    'lt'       => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць меньше :value.',
        'file'    => 'Размер файла ф поле :attribute должен быць меньше :value Килабайт(а).',
        'numeric' => 'Поле :attribute далжно быць меньше :value.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць меньше :value.',
    ],
    'lte' => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць :value иле меньше.',
        'file'    => 'Размер файла ф поле :attribute должен быць :value Килабайт(а) иле меньше.',
        'numeric' => 'Поле :attribute далжно быць :value иле меньше.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць :value иле меньше.',
    ],
    'max' => [
        'array'   => 'Колечество элементаф ф поле :attribute ни можит превышаць :max.',
        'file'    => 'Размер файла ф поле :attribute ни можит быць болие :max Килабайт(а).',
        'numeric' => 'Поле :attribute ни можит быць болие :max.',
        'string'  => 'Колечество семвалаф ф поле :attribute ни можит превышаць :max.',
    ],
    'mimes'     => 'Поле :attribute далжно быць файлам аднаво из следующех типаф: :values.',
    'mimetypes' => 'Поле :attribute далжно быць файлам аднаво из следующех типаф: :values.',
    'min'       => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць ни меньше :min.',
        'file'    => 'Размер файла ф поле :attribute должен быць ни меньше :min Килабайт(а).',
        'numeric' => 'Поле :attribute далжно быць ни меньше :min.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць ни меньше :min.',
    ],
    'multiple_of'          => 'Значенее поля :attribute далжно быць кратным :value',
    'not_in'               => 'Выбраное значенее для :attribute ашибочно.',
    'not_regex'            => 'Выбраный формат для :attribute ашибочный.',
    'numeric'              => 'Поле :attribute далжно быць числом.',
    'password'             => 'Неверный пароль.',
    'present'              => 'Поле :attribute далжно присутствеваць.',
    'prohibited'           => 'Поле :attribute запрещено.',
    'prohibited_if'        => 'Поле :attribute запрещено, кагда :other рауно :value.',
    'prohibited_unless'    => 'Поле :attribute запрещено, если :other ни входит ф :values.',
    'regex'                => 'Поле :attribute имеит ашибочный формат.',
    'relatable'            => 'Поле :attribute ни можит быць связано с этим ресурсам.',
    'required'             => 'Поле :attribute абязательно для запалненея.',
    'required_if'          => 'Поле :attribute абязательно для запалненея, кагда :other рауно :value.',
    'required_unless'      => 'Поле :attribute абязательно для запалненея, кагда :other ни рауно :values.',
    'required_with'        => 'Поле :attribute абязательно для запалненея, кагда :values указано.',
    'required_with_all'    => 'Поле :attribute абязательно для запалненея, кагда :values указано.',
    'required_without'     => 'Поле :attribute абязательно для запалненея, кагда :values ни указано.',
    'required_without_all' => 'Поле :attribute абязательно для запалненея, кагда ни адно из :values ни указано.',
    'same'                 => 'Значенея палей :attribute и :other далжны саупадаць.',
    'size'                 => [
        'array'   => 'Колечество элементаф ф поле :attribute далжно быць рауным :size.',
        'file'    => 'Размер файла ф поле :attribute должен быць раунен :size Килабайт(а).',
        'numeric' => 'Поле :attribute далжно быць рауным :size.',
        'string'  => 'Колечество семвалаф ф поле :attribute далжно быць рауным :size.',
    ],
    'starts_with' => 'Поле :attribute далжно начинацца с аднаво из следующех значеней: :values',
    'string'      => 'Поле :attribute далжно быць строкай.',
    'timezone'    => 'Поле :attribute далжно быць действительным часавым поясам.',
    'unique'      => 'Такое значенее поля :attribute уже сущиствуит.',
    'uploaded'    => 'Загрузка поля :attribute ни удалась.',
    'url'         => 'Поле :attribute имеит ашибочный формат URL.',
    'uuid'        => 'Поле :attribute далжно быць карректным UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        '_token' => [
            'in' => 'Неверный идентификатар сессее, павтаре дейстее!',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],
];
