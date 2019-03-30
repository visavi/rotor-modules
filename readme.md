# Модули для Rotor

Данные модули требуют уже установленный движок [Rotor](https://github.com/visavi/rotor) 

Модули необходимо устанавливать в директорию /app/Modules

Для включения модуля необходимо в админ-панеле перейти в раздел Модули, выбрать нужный модуль и включить его, при включении будут автоматически выполнены все миграции и созданы ссылки на статические файлы или директории

При отключении модуля, все миграции будут возвращены в первоначальное состояние, то есть созданные таблицы будут удалены, а также будут удалены ссылки

Модули можно обновлять, если новая версия модуля будет выше чем установленная, в админке появится кнопка Обновить модуль 

Чтобы создать свой модуль необходимо создать директорию название которой начинается с большой буквы, все символы должны быть латинскими 

Название директории это часть namespace (Пространство имен)
Новая директория повторяет собой директорию app в Rotor с некоторыми изменениями

Теперь в ней могут содержаться следующие файлы и директории

### Структура модуля

#### Файл module.php
Обязательный файл который описывает модуль, может содержать в себе настройки и любую другую информация

Состоит из массива
- name - Имя модуля
- description - Описание модуля
- version - Версия модуля
- author  - Автор модуля
- email - Email автора
- homepage - Сайт автора

#### Controllers
Контроллеры с пространством имен namespace App\Modules\ИмяМодуля\Controllers;

Контроллеры должны быть наследованы от \App\Controllers\BaseController или \App\Controllers\Admin\AdminController

#### Models
Модели с пространством имен namespace App\Modules\ИмяМодуля\Models;

Модели должны быть наследованы от \App\Models\BaseModel

#### resources
Директория для шаблонов (views), переводов (lang) и файлов (assets)

#### migrations
Директория для миграций, которые выполняются при установке, обновлении и удалении модуля

#### screenshots
Директория которая может содержать в себе изображения модуля

Скриншоты будут показываться на странице установки модуля, количество и размер не ограничен

#### Файл routes.php
Содержит в себе роуты 

### Шаблоны
Вызовы шаблонов должны производится с указанием namespace
К примеру `view('ИмяМодуля::директория/файл')`
Поиск шаблона будет произведен из resources/views/директрия/файл.blade.php

### Переводы
Вызовы переводов должны также производится с указанием namespace
К примеру `trans('ИмяМодуля::файл.ключ массива')`
Поиск перевода будет произведен из resources/lang/(ru|en|...)/файл.php

### Статические файлы
resources/assets - директория для статических файлов или директорий со статическими файлами, это могут быть картинки, css, js и другие файлы которые должны быть доступны напрямую, при установке модуля автоматически создается ссылка, которая будет доступна по адресу /assets/modules/преобразованноеИмяМодуля 

В модулях доступны все функции, классы и методы Rotor

Самый простой модуль c контроллером может состоять из 3 файлов: module.php, routes.php и контроллера

Если модуль только модифицирует БД, то он может состоять из 2 файлов: module.php и файла миграции

### License

The Rotor is open-sourced software licensed under the [GPL-3.0 license](http://opensource.org/licenses/GPL-3.0)
