# Модули для Rotor

Данные модули требуют уже установленный движок [Rotor](https://github.com/visavi/rotor) 

Модули необходимо устанавливать в директорию /modules

Для включения модуля необходимо в админ-панели перейти в раздел Модули, выбрать нужный модуль и включить его, при включении будут автоматически выполнены все миграции и созданы ссылки на статические файлы или директории

При удалении модуля, все миграции будут возвращены в первоначальное состояние, то есть созданные таблицы будут удалены, а также будут удалены ссылки

При отключении модуля он становится недоступен, миграции остаются без изменений

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
- panel - Ссылка на админку модуля

#### Controllers
Контроллеры с пространством имен namespace Modules\ИмяМодуля\Controllers;

Контроллеры должны быть наследованы от \App\Controllers\BaseController или \App\Controllers\Admin\AdminController

#### Models
Модели с пространством имен namespace Modules\ИмяМодуля\Models;

Модели должны быть наследованы от \App\Models\BaseModel

#### migrations
Директория для миграций, которые выполняются при установке, обновлении и удалении модуля

#### screenshots
Директория которая может содержать в себе изображения модуля

Скриншоты будут показываться на странице установки модуля, количество и размер не ограничен

#### Файл routes.php
Содержит в себе роуты 

#### resources
Директория для шаблонов (views), переводов (lang) и файлов (assets)

### Шаблоны
resources/views - вызовы шаблонов должны производится с указанием namespace
К примеру 
```php 
view('ИмяМодуля::директория/файл')
```

Поиск шаблона будет произведен из resources/views/директория/файл.blade.php

### Переводы
resources/lang - вызовы переводов должны также производится с указанием namespace
К примеру 
```php
__('ИмяМодуля::файл.ключ массива')
```
Поиск перевода будет произведен из resources/lang/(ru|en|...)/файл.php

### Настройки config.php
Если в модуле есть файл config.php, то все настройки подгружаются через вызов функции 
```php
config('ИмяМодуля.ключ массива') 
```

Содержимое файла config.php (Пример)
```php
return [
    'key'  => env('SOMETHING_KEY'), 
    'key2' => 'something_value',
];
```

### Статические файлы
resources/assets - директория для статических файлов или директорий со статическими файлами, это могут быть картинки, css, js и другие файлы которые должны быть доступны напрямую, при установке модуля автоматически создается ссылка, которая будет доступна по адресу /assets/modules/преобразованноеИмяМодуля 

### Хуки hooks.php
Хуки автоматически добавляют или меняют данные на странице


```php
use App\Classes\Hook;

// Добавляет данные
Hook::add('head', function ($content) {
    return $content . '<link rel="stylesheet" href="style.css">' . PHP_EOL;
});

// Изменяет данные
Hook::add('price', function ($value) {
    return $value + 10;
});
```

В модулях тоже можно встраивать хуки
```php
// Вызов хука
echo Hook::call('head');

// Вызов хука для изменения данных
$result = Hook::call('price', 100);

// Упрощенный вызов хука в шаблоне
@hook('head')
```

### Middleware
Промежуточное ПО

Внутри middleware.php можно добавлять свои классы промежуточного ПО

Alias - Название класса
```php
return [
    'alias' => \Modules\MyModule\Middleware\MyMiddleware::class,
];
```
Middleware автоматически применяются к группе web

### Общая информация
В модулях доступны все функции, классы и методы Rotor

Самый простой модуль c контроллером может состоять из 3 файлов: module.php, routes.php и контроллера

Если модуль только модифицирует БД, то он может состоять из: module.php и файла(ов) миграции (/migrations)

Если модуль только изменяет внешний вид, то он может состоять из: module.php и файла с хуками (hooks.php)

### License

The Rotor is open-sourced software licensed under the [GPL-3.0 license](http://opensource.org/licenses/GPL-3.0)
