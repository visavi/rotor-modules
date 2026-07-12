# Template — модуль-шаблон

*(English version below)*

Минимальный пример модуля. Скопируй папку, переименуй и адаптируй.

## Структура

```
modules/Template/
├── module.php                                 — метаданные, actions (ссылки в админке)
├── routes.php                                 — web + admin маршруты
├── helpers.php                                — глобальные функции (statsTemplate)
├── hooks.php                                  — регистрация хуков (меню, футер, админ-блок)
├── Http/Controllers/
│   ├── TemplateController.php                 — публичные действия (index, store)
│   └── Admin/TemplateController.php           — админ действия (index, delete)
├── Models/Template.php                        — модель
├── Tests/Feature/TemplateSmokeTest.php        — smoke-тесты (страницы, добавление записи)
├── database/migrations/                       — миграции
└── resources/
    ├── lang/ru/template.php                   — переводы (доступ: __('template::template.X'))
    └── views/
        ├── index.blade.php                    — публичная страница
        └── admin/index.blade.php              — админка
```

## Как переименовать в свой модуль

Допустим, новое имя — `Books`:

1. **Папка**: `modules/Template` → `modules/Books`
2. **Namespace** во всех PHP файлах: `Modules\Template` → `Modules\Books`
3. **Модель**: `Template` → `Book`
4. **Таблица**: `templates` → `books` (в миграции и `$table` модели)
5. **Маршруты**: `template.*` → `books.*`, prefix `template` → `books`
6. **View-неймспейс**: `template::` → `books::`
7. **Lang-неймспейс**: `template::template.*` → `books::books.*`, файл `lang/ru/template.php` → `lang/ru/books.php`
8. **Hook-ссылки** в `hooks.php` — поправить `route()`, иконку, путь
9. **Хелпер** `statsTemplate` → `statsBooks`, ключ кэша `statTemplate` → `statBooks`
10. **Файл миграции**: переименовать + дату обновить
11. **Тесты**: `Tests/Feature/TemplateSmokeTest.php` → `BooksSmokeTest.php`, поправить `$moduleName`, маршруты и таблицу

После — добавить запись в админке модулей и активировать (миграция накатится автоматом).

## Тесты

```
php artisan test modules/Template/Tests
```

Тесты используют `Tests\ModuleTestCase` — он сам регистрирует views/lang/routes модуля и пересоздаёт БД.

## Что не включено (добавь когда нужно)

- Комментарии (полиморфная связь + `Registry::complaint`)
- Рейтинг (`'rating' => true` в `module.php` + морф)
- Файлы/медиа (`'upload' => 'media'`/`'file'` + `UploadTrait` на модели)
- Поиск (`'search' => [...]` + `SearchableTrait`)
- Лента (`'feed' => [...]` + `FeedableTrait`)
- Спам-репорты (`'spam' => 'label'`)
- Sitemap (`Registry::sitemap()` в hooks)
- Observer (`'observers' => [Model::class => Observer::class]`)
- Console-команды (положить в `Console/`)
- Расписание (`'schedule' => fn (Schedule $s) => $s->command(...)->everyMinute()`)
- Пересчёт счётчиков (`'restatement' => [...]`)
- Настройки модуля (миграция-сид в `settings` + контроллер `SettingController`)
- Middleware (`middleware.php` с ключами `aliases` и `web`)
- Морф (`'morphs' => [Model::class]` — нужен только если модель участвует в полиморфных связях)

Готовые примеры использования см. в `modules/Guestbook`, `modules/News`, `modules/Forum`.

---

# Template — module skeleton (English)

A minimal example module for [Rotor CMS](https://github.com/visavi/rotor). Copy the folder, rename and adapt.

## Structure

```
modules/Template/
├── module.php                                 — metadata, actions (admin panel links)
├── routes.php                                 — web + admin routes
├── helpers.php                                — global functions (statsTemplate)
├── hooks.php                                  — hook registration (menu, footer, admin block)
├── Http/Controllers/
│   ├── TemplateController.php                 — public actions (index, store)
│   └── Admin/TemplateController.php           — admin actions (index, delete)
├── Models/Template.php                        — model
├── Tests/Feature/TemplateSmokeTest.php        — smoke tests (pages, record creation)
├── database/migrations/                       — migrations
└── resources/
    ├── lang/en/template.php                   — translations (access: __('template::template.X'))
    └── views/
        ├── index.blade.php                    — public page
        └── admin/index.blade.php              — admin page
```

## How to rename into your own module

Say the new name is `Books`:

1. **Folder**: `modules/Template` → `modules/Books`
2. **Namespace** in all PHP files: `Modules\Template` → `Modules\Books`
3. **Model**: `Template` → `Book`
4. **Table**: `templates` → `books` (in the migration and the model's `$table`)
5. **Routes**: `template.*` → `books.*`, prefix `template` → `books`
6. **View namespace**: `template::` → `books::`
7. **Lang namespace**: `template::template.*` → `books::books.*`, file `lang/en/template.php` → `lang/en/books.php`
8. **Hook links** in `hooks.php` — fix `route()`, icon, path
9. **Helper** `statsTemplate` → `statsBooks`, cache key `statTemplate` → `statBooks`
10. **Migration file**: rename + update the date
11. **Tests**: `Tests/Feature/TemplateSmokeTest.php` → `BooksSmokeTest.php`, update `$moduleName`, routes and table

Then add the module in the admin panel and activate it (the migration runs automatically).

## Tests

```
php artisan test modules/Template/Tests
```

Tests extend `Tests\ModuleTestCase` — it registers the module's views/lang/routes and refreshes the database.

## Not included (add when needed)

- Comments (polymorphic relation + `Registry::complaint`)
- Rating (`'rating' => true` in `module.php` + morph)
- Files/media (`'upload' => 'media'`/`'file'` + `UploadTrait` on the model)
- Search (`'search' => [...]` + `SearchableTrait`)
- Feed (`'feed' => [...]` + `FeedableTrait`)
- Spam reports (`'spam' => 'label'`)
- Sitemap (`Registry::sitemap()` in hooks)
- Observer (`'observers' => [Model::class => Observer::class]`)
- Console commands (put them in `Console/`)
- Schedule (`'schedule' => fn (Schedule $s) => $s->command(...)->everyMinute()`)
- Counter restatement (`'restatement' => [...]`)
- Module settings (seed migration into `settings` + a `SettingController`)
- Middleware (`middleware.php` with `aliases` and `web` keys)
- Morph (`'morphs' => [Model::class]` — only needed when the model takes part in polymorphic relations)

See `modules/Guestbook`, `modules/News`, `modules/Forum` for real-world examples.
