# Модули

Rotor использует модульную архитектуру. Каждый модуль — самодостаточный пакет с собственными контроллерами, моделями, вьюхами и настройками.

## Структура модуля

```
modules/MyModule/
├── Console/                  # Artisan-команды (авто-регистрация)
│   └── MyCommand.php
├── Http/
│   └── Controllers/
│       └── MyController.php
├── Models/                   # Eloquent модели
├── database/
│   └── migrations/
├── resources/
│   ├── views/                # Blade-шаблоны
│   ├── lang/                 # Переводы
│   └── assets/               # CSS/JS/изображения
├── hooks.php                 # Хуки
├── helpers.php               # Хелперы
├── module.php                # Конфигурация модуля
├── changelog.md              # История изменений по версиям
└── routes.php                # Роуты
```

## module.php

Главный конфигурационный файл модуля:

```php
<?php

use Illuminate\Console\Scheduling\Schedule;

return [
    'name'        => 'Мой модуль',
    'description' => 'Описание модуля',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',  // минимальная версия Rotor
    'author'      => 'Автор',
    'email'       => 'author@example.com',
    'homepage'    => 'https://example.com',

    // Модели модуля и их возможности
    'models' => [
        \Modules\MyModule\Models\MyEntity::class => [
            'label'  => 'Статья',
            'search' => ['view' => 'my-module::search/_items', 'with' => ['user']],
            'feed'   => ['with' => ['user', 'files'], 'view' => 'my-module::feeds/_items'],
            'upload' => 'media',
            'rating' => true,
            'spam'   => true,
        ],
    ],

    // Наблюдатели Eloquent-моделей
    'observers' => [
        \Modules\MyModule\Models\MyEntity::class => \Modules\MyModule\Observers\MyEntityObserver::class,
    ],

    // Ссылки-действия на странице модуля в админке
    'actions' => [
        '/admin/my-module' => 'Мой модуль',
    ],

    // Расписание (cron)
    'schedule' => function (Schedule $schedule) {
        $schedule->command('my-module:cleanup')->daily();
    },

    // Пересчёт счётчиков — запускается из админки кнопкой «Пересчёт»
    'restatement' => [
        'myentities' => function () {
            // DB::update('update ...');
        },
    ],

    // Публикация файлов в директории движка (копируются при установке, удаляются при отключении)
    'publish' => [
        'stubs/views'            => 'resources/views/vendor/my-module',
        'stubs/widget.blade.php' => 'resources/views/themes/default/widgets/my-module.blade.php',
    ],
];
```

## Ключ `models` — возможности контента

Самый неочевидный ключ. Он подключает модели модуля к общим механизмам ядра: глобальному поиску, ленте активности, вложениям, рейтингу, жалобам на спам. Не укажете — модель работает только внутри своих контроллеров, а ядро о ней не знает.

Каждая запись — `Класс модели => [возможности]`. Само упоминание класса регистрирует его морф-имя (`$morphName`) — на нём держатся все полиморфные связи (файлы, рейтинг, жалобы, лента), поэтому модель без морф-имени сюда добавить нельзя. Дальше подключи включают возможности по одной, все необязательны.

### `label`

```php
'label' => 'Статья',
```

Человекочитаемое имя типа. Ядро складывает в общие списки записи из разных модулей (поиск, очередь спама, рейтинг) — `label` показывается там вместо технического `articles`.

### `search`

```php
'search' => ['view' => 'my_module::search/_items', 'with' => ['user']],
```

Добавляет модель в глобальный поиск (`/search`). `view` — партиал для одной найденной записи, `with` — связи для eager-загрузки (от N+1). Без ключа записи не находятся.

### `feed`

```php
'feed' => ['view' => 'my_module::feeds/_items', 'with' => ['user', 'files']],
```

Добавляет модель в общую ленту новых публикаций. `view` — партиал записи, `with` — eager-загрузки. Необязательно: `scope` (`Closure`, сузить выборку — например только опубликованное), `poll` (`Closure` для доп. данных).

### `upload`

```php
'upload' => 'media',   // 'media' | 'file'
```

Разрешает вложения общим загрузчиком ядра. `media` — фото/видео с превью и галереей, `file` — произвольные файлы.

### `rating`

```php
'rating' => true,
```

Включает лайки/дизлайки на записи — голосование и счётчик ядро подключает само.

### `spam`

```php
'spam' => true,
```

Разрешает помечать записи как спам. Жалобы попадают в очередь в админке под меткой из `label`.

> Обработчик жалобы (что делать с записью), страницы sitemap и реакция на удаление пользователя задаются отдельно — в `hooks.php` через класс `Registry`. См. [Интеграция с ядром](/docs/rotor-module-integration).

## routes.php

Роуты модуля регистрируются автоматически. Всегда оборачивайте в middleware `web`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\MyModule\Http\Controllers\MyController;

Route::middleware('web')->group(function () {
    Route::controller(MyController::class)
        ->prefix('my-module')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store')->middleware('auth');
        });
});
```

## Контроллер

```php
<?php

declare(strict_types=1);

namespace Modules\MyModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MyController extends Controller
{
    public function index(): View
    {
        return view('my_module::index');
    }
}
```

> Имя вью-неймспейса — snake_case от имени модуля. Например, `MyModule` → `my_module::`.

## Вьюхи

Шаблоны хранятся в `resources/views/`. Для обращения используйте неймспейс:

```php
view('my_module::index')
view('my_module::posts/show')
```

В самом шаблоне:

```blade
@extends('layout')

@section('title', 'Мой модуль')

@section('content')
    <p>Привет из модуля!</p>
@stop
```

## Модели

Модели наследуют `Illuminate\Database\Eloquent\Model` как обычно. Пространство имён:

```php
namespace Modules\MyModule\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    // Обязателен для моделей, заявленных в `models` в module.php
    public static string $morphName = 'posts';

    protected $fillable = ['title', 'body', 'user_id'];
}
```

> Морф-имя — максимум 20 символов (ширина колонки `relate_type` в БД). Оно сохраняется в записях БД, поэтому после релиза модуля менять его нельзя.

## Переводы

Файлы переводов хранятся в `resources/lang/{locale}/`:

```
resources/lang/
├── ru/
│   └── my_module.php
└── en/
    └── my_module.php
```

Использование:

```php
__('my_module::my_module.some_key')
```

## Artisan-команды

Все классы в `Console/` регистрируются автоматически. Пример:

```php
<?php

namespace Modules\MyModule\Console;

use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    protected $signature = 'my-module:cleanup';
    protected $description = 'Очистка устаревших данных';

    public function handle(): int
    {
        // ...
        return self::SUCCESS;
    }
}
```

## Публикация файлов (publish)

Ключ `publish` копирует файлы модуля в произвольные директории движка — для переопределения шаблонов темы, вставки виджета в тему, файлов в корне проекта (`favicon.ico`, `robots.txt`).

```php
'publish' => [
    'stubs/views'            => 'resources/views/vendor/my-module',
    'stubs/widget.blade.php' => 'resources/views/themes/default/widgets/my-module.blade.php',
],
```

- **Источник** — относительно папки модуля, **назначение** — относительно корня проекта.
- Директория копируется как директория, файл — как файл.
- Копируется при установке/включении, удаляется при отключении/удалении.
- При обновлении ядра файлы перепубликуются автоматически (деплой и обновление через админку вызывают `module:sync`).
- Не указывайте в назначении общий каталог движка (`config`, `public`) — при отключении он удалится целиком. Используйте выделенные пути.

## Активация модуля

После создания модуля:

1. Перейдите в AdminPanel → Модули
2. Найдите модуль в списке
3. Нажмите «Установить» — выполнятся миграции, создадутся симлинки на статические файлы, опубликуются файлы из `publish`

## См. также

- [Интеграция с ядром](/docs/rotor-module-integration) — Registry: поиск, лента, жалобы, sitemap и события
- [Хуки](/docs/rotor-hooks) — вставка HTML в шаблоны ядра
- [Реестр модулей](/docs/rotor-module-registry) — установка сторонних модулей, свой реестр и распространение
