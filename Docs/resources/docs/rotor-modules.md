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
            'label'  => 'Моя сущность',                                          // метка раздела (поиск, спам, рейтинг)
            'search' => ['view' => 'my-module::search/_items', 'with' => ['user']], // глобальный поиск
            'feed'   => ['with' => ['user', 'files'], 'view' => 'my-module::feeds/_items'], // лента активности
            'upload' => 'media',                                                 // media | file
            'rating' => true,                                                    // лайки/дизлайки
            'spam'   => true,                                                    // пометка спама
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

## Установка сторонних модулей

- **Каталог** — AdminPanel → Модули → Каталог: модули из подключённых реестров скачиваются и устанавливаются автоматически.
- **ZIP-архив** — AdminPanel → Модули → Загрузить: из файла или по прямой ссылке.
- **Вручную** — распакуйте модуль в `modules/ИмяМодуля` и нажмите «Установить» в админке.

## Свой реестр модулей

Реестр — это `registry.json`, который ядро скачивает по URL и показывает в каталоге модулей. Любой автор может собрать свои модули, опубликовать реестр, а пользователи добавят его ссылку у себя.

### 1. История изменений (`changelog.md`)

В корне модуля держите файл `changelog.md` с секциями по версиям. Заголовок версии обязателен (`## X.Y.Z`), тело — произвольный текст (markdown знать не нужно):

```markdown
## 1.1.0
- Добавлена выгрузка в CSV
- Исправлена пагинация

## 1.0.0
- Первый релиз
```

Ядро показывает changelog на странице модуля: «История изменений» (всегда) и «Что нового в версии X» (при доступном обновлении). Файла нет — ничего не ломается.

### 2. Сборка реестра

Команда `module:registry` собирает `registry.json` из локальных модулей: читает `module.php` (имя, версия, requires, автор…) и `changelog.md`.

```bash
# все модули каталога → файл
php artisan module:registry modules \
    --name="Мои модули" \
    --base-url=https://example.com/modules \
    --output=registry.json

# один модуль (для инкремента в готовый реестр)
php artisan module:registry modules/MyModule \
    --existing=registry.json --output=registry.json
```

Опции:

- `--name` — название реестра.
- `--base-url` — база для `download_url`. Ссылка на ZIP строится как `<base>/<Модуль>-<версия>/<Модуль>.zip`.
- `--output` (`-o`) — файл вывода (без него — в stdout).
- `--existing` — существующий `registry.json`: старые версии модулей сохраняются, текущая дописывается/перезаписывается.

### 3. Публикация

1. Соберите ZIP каждого модуля и выложите так, чтобы адрес совпал с `download_url` (`<base>/<Модуль>-<версия>/<Модуль>.zip`). На GitHub удобно через Releases.
2. Выложите `registry.json` по постоянному URL.

### 4. Обновление

1. Поднимите `version` в `module.php`.
2. Допишите секцию `## X.Y.Z` в `changelog.md`.
3. Соберите новый ZIP версии.
4. Пересоберите реестр с `--existing` (накопит версии) и перезалейте `registry.json`.

У пользователей кэш реестра обновится сам по TTL.

### 5. Подключение пользователем

AdminPanel → Модули → Реестры → вставить URL вашего `registry.json`. После этого модули появятся в каталоге (AdminPanel → Модули → Каталог) и установятся в один клик.

> CI-вариант: репозиторий [rotor-modules](https://github.com/visavi/rotor-modules) собирает реестр автоматически при пуше — workflow создаёт релизы и публикует `registry.json`. Для своих модулей это не обязательно: достаточно команды `module:registry`.

## См. также

- [Хуки](/docs/rotor-hooks) — вставка HTML в шаблоны ядра
- [API модулей](/docs/rotor-module-api) — Registry: поиск, лента, жалобы, sitemap и события
