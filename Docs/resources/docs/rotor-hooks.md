# Хуки

Хуки позволяют вставлять произвольный HTML в предопределённые точки шаблона без редактирования его файлов.

## Как работают хуки

В шаблонах движка расставлены метки вида `@hook('hookName')`. При рендеринге туда подставляется контент всех зарегистрированных обработчиков.

Все хуки регистрируются в файле `app/hooks.php` (глобальные) или `modules/MyModule/hooks.php` (хуки модуля).

## Регистрация хука

```php
use App\Classes\Hook;

// Строка — вставляется как есть
Hook::add('head', '<link rel="stylesheet" href="/assets/my.css">');

// Callable — вызывается при рендеринге, возвращает строку или null
Hook::add('footer', function () {
    return '<script src="/assets/my.js"></script>';
});

// С приоритетом (чем выше — тем раньше выводится)
Hook::add('sidebarMenu', function () {
    return '<li>...</li>';
}, priority: 10);
```

Если callable возвращает `null` или `''` — хук не выводится.

## Доступные точки вставки

### `<head>`
| Хук | Описание |
|-----|----------|
| `head` | Внутри `<head>`, перед закрывающим тегом |

### Шапка
| Хук | Описание |
|-----|----------|
| `header` | После заголовка страницы |

### Боковая панель
| Хук | Описание |
|-----|----------|
| `sidebarMenu` | Пункты главного меню |
| `sidebarTreeviewStart` | Начало дерева пользователя (авторизован) |
| `sidebarTreeviewEnd` | Конец дерева пользователя |
| `sidebarTreeviewGuestStart` | Начало дерева гостя |
| `sidebarTreeviewGuestEnd` | Конец дерева гостя |
| `sidebarFooterStart` | Начало футера сайдбара |
| `sidebarFooterEnd` | Конец футера сайдбара |

### Контент
| Хук | Описание |
|-----|----------|
| `contentStart` | Перед основным контентом |
| `contentEnd` | После основного контента |
| `advertTop` | Реклама над контентом |
| `advertBottom` | Реклама под контентом |

### Футер
| Хук | Описание |
|-----|----------|
| `footerColumnMiddle` | Колонка в футере |
| `footerEnd` | Конец футера |
| `footer` | Перед закрывающим `</body>` |

## Примеры

### Добавить пункт меню

```php
Hook::add('sidebarMenu', function () {
    return '<li>
        <a class="menu-item' . (request()->is('my-page*') ? ' active' : '') . '" href="/my-page">
            <i class="menu-icon fa-solid fa-star"></i>
            <span class="menu-label">Моя страница</span>
        </a>
    </li>';
}, priority: 5);
```

### Вставить счётчик только на главной

```php
Hook::add('footerEnd', function () {
    if (request()->routeIs('home')) {
        return '<!-- Yandex.Metrika counter -->...<!-- /Yandex.Metrika counter -->';
    }

    return null;
});
```

### Добавить скрипт

```php
Hook::add('footer', '<script type="module" src="/assets/modules/my_module/app.js"></script>');
```

### Добавить ссылку в футер

```php
Hook::add('footerColumnMiddle', '<li><a class="footer-item" href="/about">О сайте</a></li>');
```

## Добавление хука в шаблон

Если вы разрабатываете собственную тему и хотите добавить новую точку вставки, используйте директиву `@hook`:

```blade
{{-- В blade-шаблоне --}}
@hook('myCustomHook')
```

В коде HTML будет HTML-комментарий `<!--@myCustomHook-->` плюс вывод всех зарегистрированных обработчиков.

## Класс Hook

```php
Hook::add(string $hookName, string|callable $value, int $priority = 0): void
Hook::call(string $hookName, ...$args): string
Hook::has(string $hookName): bool
Hook::getHooks(): array
```
