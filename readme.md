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

Reestry — источники, из которых каталог берёт список доступных модулей. В **Модули → Реестры** можно добавить свой или сторонний реестр в формате JSON.

Официальный реестр этого репозитория:
```
https://github.com/visavi/rotor-modules/releases/download/registry/registry.json
```

Файл `registry.json` обновляется автоматически через GitHub Actions при каждом пуше в репозиторий.

---

## Структура модуля

```
MyModule/
├── module.php          # обязательный — описание и настройки модуля
├── routes.php          # маршруты веб и API
├── hooks.php           # хуки — встраивание в шаблоны движка
├── helpers.php         # глобальные вспомогательные функции
├── middleware.php      # промежуточное ПО (регистрация алиасов)
├── config.php          # конфигурация модуля
├── Http/
│   ├── Controllers/    # контроллеры (namespace Modules\MyModule\Http\Controllers)
│   └── Resources/      # API-ресурсы (namespace Modules\MyModule\Http\Resources)
├── Models/             # модели Eloquent (namespace Modules\MyModule\Models)
├── database/
│   └── migrations/     # миграции БД: выполняются при установке, обновлении и откатываются при удалении модуля
├── resources/
│   ├── views/          # Blade-шаблоны, вызов: view('MyModule::dir/file')
│   ├── lang/           # переводы по языкам (ru, en, ua...), вызов: __('MyModule::file.key')
│   └── assets/         # статические файлы (css, js, img); при установке создаётся симлинк,
│                       # файлы доступны по адресу /assets/modules/my-module/
└── screenshots/        # скриншоты модуля — отображаются на странице модуля в админке
```

---

## Файл module.php

Обязательный файл. Возвращает массив с метаданными и настройками модуля:

```php
return [
    'name'        => 'Название модуля',
    'description' => 'Краткое описание',
    'version'     => '1.0.0',
    'author'      => 'Автор',
    'email'       => 'author@example.com',
    'homepage'    => 'https://example.com',
    'requires'    => '>=13.0.0',   // минимальная версия движка

    // Регистрация полиморфной связи
    'morph'   => MyModel::class,

    // Интеграция с поиском
    'search'  => [
        'label' => 'Метка в поиске',
        'view'  => 'MyModule::search/_results',
    ],

    // Интеграция с лентой
    'feed'    => [
        'withs' => ['user', 'files'],
        'view'  => 'MyModule::feeds/_feed',
    ],

    // Тип загружаемых файлов: 'media', 'image', 'file'
    'upload'  => 'media',

    // Включить рейтинг
    'rating'  => true,

    // Ссылки в панели администратора
    'panel'   => [
        '/admin/my-module' => 'Мой модуль',
    ],
];
```

### Описание интеграционных полей

**`requires`** — минимальная версия движка Rotor. Если версия не совместима, в каталоге модуль помечается как «Несовместим» и кнопки установки/обновления скрываются.

**`morph`** — регистрирует модель в полиморфной карте Laravel (`morphMap`). Нужно если модель участвует в полиморфных связях — например, хранит комментарии, лайки, жалобы или файлы через общие таблицы движка.

**`search`** — подключает модуль к глобальному поиску. `label` — название раздела в результатах поиска, `view` — шаблон для отображения одного результата.

**`feed`** — подключает записи модуля к общей ленте активности. `withs` — список отношений для жадной загрузки (eager load), `view` — шаблон для отображения одной записи в ленте.

**`upload`** — разрешает загрузку файлов к записям модуля. Значения:
- `media` — изображения и видео
- `file` — любые файлы

**`rating`** — включает систему лайков/дизлайков для записей модуля.

**`panel`** — массив ссылок, которые добавляются в навигацию панели администратора. Ключ — URL, значение — название пункта меню.

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

class MyModel extends \Illuminate\Database\Eloquent\Model { }
```

---

## Маршруты (routes.php)

```php
use Illuminate\Support\Facades\Route;
use Modules\MyModule\Http\Controllers\MyController;

Route::get('/my-module', [MyController::class, 'index'])->name('my-module.index');
```

---

## Хуки (hooks.php)

Хуки позволяют встраивать контент в шаблоны движка без изменения его кода:

```php
use App\Classes\Hook;

// Добавить CSS в <head>
Hook::add('head', function (string $content) {
    return $content . '<link rel="stylesheet" href="/assets/modules/my-module/style.css">' . PHP_EOL;
});

// Добавить скрипт в footer
Hook::add('footer', function (string $content) {
    return $content . '<script type="module" src="/assets/modules/my-module/app.js"></script>' . PHP_EOL;
});

// Изменить значение
Hook::add('price', function (int $value) {
    return $value + 10;
});
```

Вызов хука в шаблоне:
```blade
@hook('head')
```

Вызов с изменением данных:
```php
$price = Hook::call('price', 100);
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

---

## Статические файлы

Файлы из `resources/assets/` доступны по адресу:
```
/assets/modules/my-module/
```

Симлинк создаётся автоматически при установке модуля.

---

## Middleware (middleware.php)

```php
return [
    'my-alias' => \Modules\MyModule\Middleware\MyMiddleware::class,
];
```

Middleware автоматически регистрируется в группе `web`.

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

---

## License

The Rotor is open-sourced software licensed under the [GPL-3.0 license](http://opensource.org/licenses/GPL-3.0)
