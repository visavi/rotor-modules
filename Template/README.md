# Template — модуль-шаблон

Минимальный пример модуля. Скопируй папку, переименуй и адаптируй.

## Структура

```
modules/Template/
├── module.php                                 — метаданные, panel (ссылки в админке)
├── routes.php                                 — web + admin маршруты
├── helpers.php                                — глобальные функции (statsTemplate)
├── hooks.php                                  — регистрация хуков (меню, футер, админ-блок)
├── Http/Controllers/
│   ├── TemplateController.php                 — публичные действия (index, store)
│   └── Admin/TemplateController.php           — админ действия (index, delete)
├── Models/Template.php                        — модель
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

После — добавить запись в админке модулей и активировать (миграция накатится автоматом).

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
- Тесты (`tests/Feature/`)

Готовые примеры использования см. в `modules/Guestbook`, `modules/News`, `modules/Forum`.
