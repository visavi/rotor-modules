# Сборка ресурсов

Rotor использует [Vite](https://vitejs.dev/) для сборки CSS и JavaScript.

## Установка зависимостей

```bash
npm ci
```

`npm ci` — строгая установка из `package-lock.json`. Используйте вместо `npm install` для воспроизводимой сборки.

## Команды сборки

```bash
npm run dev        # сборка для разработки (без минификации)
npm run build      # production-сборка (минификация, хеши)
npm run build:brotli  # production + brotli-сжатие
npm run watch      # пересборка при изменении файлов
```

## Где хранятся файлы сборки

Vite собирает файлы в `public/build/`:

```
public/build/
├── manifest.json        # карта файлов (используется @vite() в шаблонах)
├── assets/
│   ├── bootstrap-[hash].css   # общий Bootstrap
│   ├── vendor-[hash].css      # общие библиотеки
│   ├── main-[hash].js         # общий js ядра
│   ├── app-[hash].css         # стили темы
│   └── ...
├── lang/
│   └── ru-[hash].js     # переводы для js
└── fonts/
    ├── fa-solid-900.woff2
    └── ...
```

Папка `public/build/` генерируется автоматически и не хранится в git (`.gitignore`).

## Структура исходников

Все исходники лежат в `resources`, в `public` остаются только готовые файлы —
изображения, флаги, шрифты:

```
resources/
├── js/                       # js ядра
│   ├── main.js               # общий скрипт: Bootstrap, ajax, обработчики форм
│   ├── sidebar.js            # логика сайдбара
│   ├── tiptap.js             # редактор (грузится динамически)
│   └── chartist.js           # графики (подключает модуль Counter)
├── css/
│   ├── bootstrap.scss        # общий Bootstrap для тем без своей сборки
│   ├── main.css              # стили ядра и дефолты, которые ждут вьюхи
│   ├── tiptap.css
│   ├── prettify.css          # подсветка кода, использует переменные --base-*
│   └── chartist.css
└── themes/
    ├── vendor.scss           # общие библиотеки: FontAwesome, fancybox, notyf
    └── default/
        ├── js/app.js         # точка входа темы
        └── sass/
            ├── app.scss      # список файлов темы
            ├── _variables.scss  # переменные вёрстки + настройки Bootstrap
            ├── _header.scss
            └── ...
```

## Точки входа (entrypoints)

Перечислены в `vite.config.js`:

```
resources/themes/vendor.scss          # общие библиотеки, нужны каждой теме
resources/themes/default/js/app.js    # тема Default
resources/themes/mobile/js/app.js     # тема Mobile
resources/themes/motor/js/app.js      # тема Motor
resources/themes/fresh/js/app.js      # тема Fresh
resources/themes/nordic/js/app.js     # тема Nordic
resources/themes/newspaper/js/app.js  # тема Newspaper
resources/css/bootstrap.scss          # общий Bootstrap + bootstrap5-tags
resources/js/main.js                  # общий js ядра
resources/css/chartist.css            # графики, подключает модуль Counter
resources/js/chartist.js
```

## Алиасы путей

В `vite.config.js` настроены алиасы для удобного импорта:

```js
import 'js/main.js'           // → resources/js/main.js
@import 'css/main'            // → resources/css/main.css
@import 'themes/default/...'  // → resources/themes/default/...
@import 'fa/...'              // → node_modules/@fortawesome/fontawesome-free
```

## Подключение в шаблоне

Ресурсы подключаются директивой `@vite` в `layout.blade.php` темы:

```blade
@vite('resources/css/bootstrap.scss')
@vite('resources/themes/vendor.scss')
@vite('resources/themes/default/js/app.js')
```

Порядок важен: сначала Bootstrap, затем общие библиотеки, затем стили темы —
каждый следующий файл может переопределять предыдущий.

Laravel подставляет URL с хешем в production или адрес dev-сервера при разработке.

## Bootstrap: общий или свой

Bootstrap собирается один раз в `resources/css/bootstrap.scss` и подключается всеми
темами. Настройки, одинаковые для всего проекта (`$font-size-base`, `$link-decoration`
и другие), заданы там же — до его импорта.

Оформление тема задаёт **CSS-переменными** в своём `_variables.scss`, без пересборки
Bootstrap:

```scss
:root {
    --bs-primary: #297dad;
    --bs-primary-rgb: 41, 125, 173;
    --bs-body-font-family: 'Open Sans', sans-serif;
}

// Часть значений Bootstrap запекает при компиляции — их задаём явно
.btn {
    --bs-btn-border-radius: 3px;
    --bs-btn-font-weight: 700;
}
```

В scss-переменных остаётся только то, что нужно самому компилятору: ветвления `@if`,
арифметика цвета (`darken()`, `rgba()`) и условия `@media` — в них `var()` не работает.

## Тема без сборки

Тему можно сделать вообще без npm и Vite — пример в `resources/views/themes/simple`.
Она берёт общие файлы ядра, а свои стили и скрипты кладёт обычными файлами
в `public/themes/<имя>`:

```blade
@vite('resources/css/bootstrap.scss')
@vite('resources/themes/vendor.scss')
@vite('resources/js/main.js')
<link rel="stylesheet" href="{{ asset('/themes/simple/style.css') }}">
<script src="{{ asset('/themes/simple/app.js') }}" defer></script>
```

Такой теме не нужны правки `vite.config.js`: все точки входа уже собраны.

## Что тема получает от ядра

`resources/css/main.css` объявляет то, на что рассчитывают вьюхи ядра и модулей.
Тема может это переопределить, но объявлять заново не обязана:

| Что | Зачем |
|-----|-------|
| `--base-*` | палитра подсветки кода для `prettify.css` |
| `.badge.bg-adaptive`, `.bg-notify`, `.badge.menu-badge` | бейджи счётчиков в меню и шапке |
| `.btn.btn-adaptive` | кнопка, подстраивающаяся под тему |
| `.form-check-input:checked`, `.list-group`, `.progress`, `.accordion` | компоненты, которым Bootstrap запекает акцентный цвет |

Классы ядра пишутся с повышенной специфичностью (`.btn.btn-adaptive`, а не
`.btn-adaptive`): у Bootstrap есть правила с теми же переменными, и он может идти
в каскаде позже.

## Переводы для js

Плагин `langPlugin` собирает `resources/lang/{locale}/main.json` в
`public/build/lang/{locale}-[hash].js` и объявляет `window.translations`.
Подключается директивой `@translation` — если сборки нет (например, язык
доустановили модулем), переводы уходят в разметку прямо из json.

## Разработка с hot-reload

```bash
npm run dev
```

Запускает Vite dev-сервер на `localhost:5173`. Изменения в CSS/JS применяются мгновенно без перезагрузки страницы.

Для корректной работы в `.env`:

```ini
APP_URL=http://localhost:8000
VITE_DEV_SERVER_URL=http://localhost:5173
```

## Brotli-сжатие

```bash
npm run build:brotli
```

Создаёт `.br` версии файлов рядом с основными. Для отдачи `.br` файлов настройте Nginx:

```nginx
brotli on;
brotli_static on;
brotli_types text/css application/javascript;
```

## Основные зависимости

| Пакет | Назначение |
|-------|-----------|
| Bootstrap 5.3 | UI-фреймворк |
| Font Awesome 7 | Иконки |
| Tiptap 3 | WYSIWYG редактор |
| Fancyapps UI | Галерея / лайтбокс |
| Chartist | Графики |
| Notyf | Всплывающие уведомления |
