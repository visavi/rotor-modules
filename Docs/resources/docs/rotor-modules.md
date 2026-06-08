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
            'feed'   => ['withs' => ['user', 'files'], 'view' => 'my-module::feeds/_items'], // лента активности
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

## Активация модуля

После создания модуля:

1. Перейдите в AdminPanel → Модули
2. Найдите модуль в списке
3. Нажмите «Включить»

Или через Artisan:

```bash
php artisan module:enable MyModule
```

## Установка модуля из пакета

Сторонние модули устанавливаются через Composer и копируются в папку `modules/`:

```bash
composer require vendor/rotor-my-module
php artisan module:install MyModule
```
