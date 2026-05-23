# Модули для Rotor

Официальный репозиторий модулей для движка [Rotor](https://github.com/visavi/rotor).

## Установка

### Через каталог модулей (рекомендуется)

В админ-панели перейдите в **Модули → Каталог**. Все доступные модули из подключённых реестров отображаются там. Нажмите «Установить» — модуль будет скачан и распакован автоматически.

### Через ZIP-архив

В **Модули → Загрузить** можно установить модуль из ZIP-файла или по прямой ссылке.

### Вручную

Распакуйте модуль в директорию `/modules/ИмяМодуля`, после чего в админ-панели нажмите «Установить».

---

## Управление модулями

- **Установка** — выполняются миграции, создаются симлинки на статические файлы, подключаются настройки, хуки и маршруты
- **Отключение** — модуль становится недоступен, данные в БД сохраняются
- **Включение** — модуль возобновляет работу с теми же данными
- **Обновление** — если версия в `module.php` выше установленной, в админке появляется кнопка «Обновить»
- **Удаление** — откатываются миграции, удаляются симлинки и данные модуля

---

## Реестры модулей

Реестры — источники, из которых каталог берёт список доступных модулей. В **Модули → Реестры** можно добавить свой или сторонний реестр в формате JSON.

Официальный реестр этого репозитория:
```
https://github.com/visavi/rotor-modules/releases/download/registry/registry.json
```

Файл `registry.json` обновляется автоматически через GitHub Actions при каждом пуше в репозиторий.

---

## Структура модуля

```
MyModule/
├── module.php          # обязательный — метаданные и возможности модуля
├── routes.php          # маршруты веб и API
├── hooks.php           # вставки в шаблоны через Hook::add и регистрации Registry/Restatement
├── helpers.php         # глобальные вспомогательные функции
├── middleware.php      # регистрация middleware (алиасы и/или группа web)
├── config.php          # конфигурация модуля (config('MyModule.key'))
├── Http/
│   ├── Controllers/    # контроллеры (Modules\MyModule\Http\Controllers)
│   ├── Requests/       # FormRequest классы
│   └── Resources/      # API-ресурсы
├── Models/             # модели Eloquent (Modules\MyModule\Models)
├── Observers/          # наблюдатели моделей
├── Middleware/         # классы middleware
├── Services/           # сервисные классы
├── Console/            # консольные команды — автоматически регистрируются
├── database/
│   └── migrations/     # миграции БД — выполняются при установке/обновлении,
│                       # откатываются при удалении
├── resources/
│   ├── views/          # Blade-шаблоны: view('MyModule::dir/file')
│   ├── lang/           # переводы по языкам: __('MyModule::file.key')
│   └── assets/         # статические файлы (css, js, img);
│                       # симлинк создаётся на /assets/modules/my-module/
└── screenshots/        # скриншоты модуля для карточки в админке
```

Все поддиректории необязательные — создавай только нужные.

---

## Файл module.php

Обязательный файл. Возвращает массив с метаданными и возможностями модуля:

```php
use Modules\MyModule\Models\MyModel;
use Modules\MyModule\Observers\MyObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

return [
    'name'        => 'Название модуля',
    'description' => 'Краткое описание',
    'info'        => '<p>Длинное описание с HTML, показывается на странице модуля в админке</p>',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Автор',
    'email'       => 'author@example.com',
    'homepage'    => 'https://example.com',

    // Возможности моделей модуля
    'models' => [
        MyModel::class => [
            'search' => ['label' => 'Метка', 'view' => 'MyModule::search/_results', 'with' => ['user']],
            'feed'   => ['withs' => ['user', 'files'], 'view' => 'MyModule::feeds/_feed'],
            'upload' => 'media',
            'rating' => true,
            'spam'   => 'Метка спама',
        ],
    ],

    // Наблюдатели моделей
    'observers' => [
        MyModel::class => MyObserver::class,
    ],

    // Планировщик задач
    'schedule' => function (Schedule $schedule) {
        $schedule->command('my-module:cleanup')->daily();
    },

    // Пересчёты — вызываются из админки кнопкой «Пересчёт»
    'restatement' => [
        'mymodel' => function () {
            DB::update('update ...');
        },
    ],

    // Ссылки в админ-панели
    'panel' => [
        '/admin/my-module' => 'Мой модуль',
    ],
];
```

### Описание полей

**`name`, `description`, `author`, `email`, `homepage`** — отображаются в карточке модуля.

**`info`** — длинное описание с HTML, видно на странице модуля в админке. Сюда удобно класть инструкции по подключению.

**`version`** — текущая версия модуля. Если в `module.php` версия выше установленной, в админке появляется кнопка «Обновить».

**`requires`** — минимальная версия движка Rotor. При несовместимости модуль помечается в каталоге как «Несовместим».

**`models`** — массив `Class::class => [возможности]`. Каждая модель автоматически регистрируется в `morphMap` Laravel. Доступные возможности:

| Ключ | Описание |
|---|---|
| `search` | Подключает модель к глобальному поиску. `label` — название раздела в результатах, `view` — шаблон одного результата, `with` (опц.) — отношения для eager-load. |
| `feed` | Подключает к общей ленте активности. `withs` — отношения для eager-load, `view` — шаблон записи. |
| `upload` | Разрешает прикреплять файлы. `media` — изображения и видео, `file` — любые файлы. |
| `rating` | `true` — включает лайки/дизлайки. |
| `spam` | Метка раздела на странице «Спам» в админке. Записи можно помечать как спам. |

Если модель нужна только для `morphMap` (например, для полиморфных связей), но не имеет возможностей — оставь пустой массив:
```php
'models' => [
    Vote::class => [],
],
```

**`observers`** — массив `Class::class => Observer::class`. Регистрирует Eloquent-наблюдателей.

**`schedule`** — замыкание, получающее `Schedule` Laravel. Регистрирует периодические задачи.

**`restatement`** — массив `'ключ' => callable`. Пересчёты счётчиков, запускаются из админки или вручную через `Restatement::run('ключ')`.

**`panel`** — массив `URL => 'Название'`. Ссылки добавляются в навигацию админ-панели.

---

## Маршруты (routes.php)

```php
use Illuminate\Support\Facades\Route;
use Modules\MyModule\Http\Controllers\MyController;

Route::middleware('web')
    ->controller(MyController::class)
    ->prefix('my-module')
    ->name('my-module.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'view')->name('view');
    });

// Админка
Route::middleware(['web', 'check.admin', 'admin.logger'])
    ->prefix('admin')
    ->group(function () {
        // ...
    });
```

---

## Контроллеры

Пространство имён: `Modules\MyModule\Http\Controllers`

```php
namespace Modules\MyModule\Http\Controllers;

class MyController extends \App\Http\Controllers\Controller
{
    public function index() { ... }
}
```

Административные контроллеры размещаются в `Http/Controllers/Admin/` и наследуются от `\App\Http\Controllers\Admin\AdminController`.

---

## Модели

Пространство имён: `Modules\MyModule\Models`

```php
namespace Modules\MyModule\Models;

class MyModel extends \Illuminate\Database\Eloquent\Model
{
    public static string $morphName = 'mymodels';
}
```

`$morphName` обязательно у моделей, заявленных в `models` (используется ядром для регистрации возможностей).

---

## Хуки (hooks.php)

Файл `hooks.php` содержит:
- вставки в шаблоны через `Hook::add` (UI-расширения);
- регистрации в `Registry` для возможностей, которые не привязаны к одной модели (sitemap, complaint, pollResolver, onDeleteUser, onAdminDeleteUser);
- регистрации `Restatement::register` (если не объявлено в `module.php`).

### Hook::add

```php
use App\Classes\Hook;

// Добавить CSS в <head>
Hook::add('head', function (string $content) {
    return $content . '<link rel="stylesheet" href="/assets/modules/my-module/style.css">' . PHP_EOL;
});

// Изменить значение
Hook::add('price', function (int $value) {
    return $value + 10;
});
```

Вызов в шаблоне:
```blade
@hook('head')
```

Вызов с изменением данных:
```php
$price = Hook::call('price', 100);
```

### Registry

```php
use App\Classes\Registry;
use Modules\MyModule\Models\MyModel;

// Жалобы — обработчик клика на «пожаловаться»
Registry::complaint(MyModel::$morphName, function (int $id) {
    $model = MyModel::query()->find($id);
    return ['model' => $model, 'path' => $model?->getViewUrl(false)];
});

// Sitemap
Registry::sitemap('mymodels', function () {
    return [['loc' => route('my-module.index'), 'lastmod' => gmdate('c')]];
});

// Удаление пользователя — что подчистить
Registry::onDeleteUser(function (\App\Models\User $user) {
    MyModel::query()->where('user_id', $user->id)->delete();
});
```

---

## Шаблоны

Файлы в `resources/views/` вызываются с указанием неймспейса модуля:

```php
view('MyModule::directory/file')
// → resources/views/directory/file.blade.php
```

---

## Переводы

Файлы в `resources/lang/ru/`, `resources/lang/en/` и т.д.:

```php
__('MyModule::file.key')
// → resources/lang/ru/file.php → ['key' => '...']
```

---

## Конфигурация (config.php)

```php
// config.php
return [
    'api_key' => env('MY_MODULE_API_KEY'),
    'limit'   => 10,
];

// Использование
config('MyModule.api_key');
```

Значения из админки записываются в поле `settings` модуля и сливаются поверх `config.php` при загрузке.

---

## Helpers (helpers.php)

Глобальные функции, доступные везде:

```php
if (! function_exists('statsMyModule')) {
    function statsMyModule(): string
    {
        return (string) MyModel::query()->count();
    }
}
```

---

## Middleware (middleware.php)

```php
use Modules\MyModule\Middleware\MyMiddleware;

return [
    // Алиасы для применения в routes.php через ->middleware('alias')
    'aliases' => [
        'my-alias' => MyMiddleware::class,
    ],

    // Middleware, добавляемые в группу web автоматически
    'web' => [
        MyMiddleware::class,
    ],
];
```

---

## Консольные команды

Файлы в `Console/` подхватываются автоматически. Имя класса = имя файла:

```php
// Console/Cleanup.php
namespace Modules\MyModule\Console;

use Illuminate\Console\Command;

class Cleanup extends Command
{
    protected $signature = 'my-module:cleanup';

    public function handle(): void { /* ... */ }
}
```

---

## Статические файлы

Файлы из `resources/assets/` доступны по адресу:
```
/assets/modules/my-module/
```

Симлинк создаётся автоматически при установке модуля.

---

## Примеры минимальных модулей

**Только маршруты и контроллер:**
```
MyModule/module.php, routes.php, Http/Controllers/MyController.php
```

**Только миграции (изменение БД):**
```
MyModule/module.php, database/migrations/
```

**Только внешний вид (хуки):**
```
MyModule/module.php, hooks.php
```

См. модуль `Template` — минимальный шаблон для старта.

---

## License

The Rotor is open-sourced software licensed under the [GPL-3.0 license](http://opensource.org/licenses/GPL-3.0)
