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
│   ├── app-[hash].js
│   ├── app-[hash].css
│   └── ...
└── fonts/
    ├── fa-solid-900.woff2
    └── ...
```

Папка `public/build/` генерируется автоматически и не хранится в git (`.gitignore`).

## Точки входа (entrypoints)

Каждая тема имеет свой `app.js`. В `vite.config.js` перечислены все точки входа:

```
resources/themes/vendor.scss          # общие стили (Bootstrap, FontAwesome)
resources/themes/default/js/app.js    # тема Default
resources/themes/mobile/js/app.js     # тема Mobile
resources/themes/motor/js/app.js      # тема Motor
resources/themes/fresh/js/app.js      # тема Fresh
resources/themes/nordic/js/app.js     # тема Nordic
resources/themes/newspaper/js/app.js  # тема Newspaper
```

## Структура исходников темы

```
resources/themes/
├── vendor.scss               # общие зависимости: Bootstrap, FontAwesome, prettify
├── default/
│   ├── js/
│   │   ├── app.js            # точка входа JS
│   │   └── sidebar.js        # логика сайдбара
│   └── sass/
│       └── app.scss          # стили темы
└── ...
```

## Алиасы путей

В `vite.config.js` настроены алиасы для удобного импорта:

```js
import 'js/main.js'           // → public/assets/js/main.js
import 'css/styles.css'       // → public/assets/css/styles.css
import 'fa/...'               // → node_modules/@fortawesome/fontawesome-free
```

## Подключение в шаблоне

Vite-ресурсы подключаются через директиву `@vite`:

```blade
@vite('resources/themes/vendor.scss')
@vite('resources/themes/default/js/app.js')
```

Laravel автоматически подставляет правильный URL с хешем в production или адрес dev-сервера при разработке.

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
