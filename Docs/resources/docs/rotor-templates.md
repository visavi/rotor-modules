# Шаблоны

Rotor поддерживает несколько тем оформления. Темы хранятся в `resources/views/themes/`.

## Структура темы

```
resources/views/themes/my-theme/
├── layout.blade.php     # Основной layout
├── navbar.blade.php     # Навигационная панель
├── sidebar.blade.php    # Боковое меню
└── footer.blade.php     # Футер
```

## Выбор темы

Активная тема устанавливается в AdminPanel → Настройки → Оформление.

Темы можно переключать программно через настройку `theme`:

```php
setting('theme')  // возвращает имя текущей темы, например 'default'
```

## Основной layout

Каждый `layout.blade.php` должен подключать глобальный `layout`:

```blade
@extends('theme::layout')
```

А страницы модулей расширяют `layout`:

```blade
@extends('layout')
```

`layout.blade.php` в корне (`resources/views/layout.blade.php`) в свою очередь расширяет `theme::layout`.

## Секции

| Секция | Описание |
|--------|----------|
| `title` | Заголовок страницы (тег `<title>` и `<h1>`) |
| `description` | Мета-описание |
| `canonical` | Канонический URL |
| `breadcrumb` | Хлебные крошки |
| `content` | Основное содержимое |
| `header` | Дополнительный контент в шапке |

### Пример страницы

```blade
@extends('layout')

@section('title', 'Мой заголовок')

@section('description', 'Описание страницы для SEO')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">Мой раздел</li>
        </ol>
    </nav>
@stop

@section('content')
    <p>Контент страницы</p>
@stop
```

## Стеки скриптов и стилей

Для добавления CSS/JS только на конкретные страницы используйте `@push`:

```blade
@push('styles')
    <link rel="stylesheet" href="/assets/my.css">
@endpush

@push('scripts')
    <script src="/assets/my.js"></script>
@endpush
```

В теме они выводятся через:

```blade
@stack('styles')
@stack('scripts')
```

## Создание темы

1. Скопируйте папку `resources/views/themes/default` с новым именем
2. Отредактируйте `layout.blade.php`, `navbar.blade.php`, `sidebar.blade.php`, `footer.blade.php`
3. Добавьте CSS в `resources/themes/` (или подключите из CDN)
4. Активируйте в AdminPanel → Настройки

## Хелперы в шаблонах

```blade
{{ setting('title') }}          {{-- настройка сайта --}}
{{ getUser() }}                 {{-- текущий пользователь или null --}}
{{ isAdmin() }}                 {{-- true если администратор --}}
{{ showOnline() }}              {{-- количество онлайн --}}
@hook('hookName')               {{-- точка вставки хука --}}
```

## Иконки

Движок использует [Font Awesome 7](https://fontawesome.com/icons). Примеры:

```blade
<i class="fas fa-home"></i>
<i class="far fa-user"></i>
<i class="fa-solid fa-gear"></i>
```

## Ассеты модуля

Файлы CSS/JS/изображений для модуля размещаются в `modules/MyModule/resources/assets/`.

При установке модуля они копируются в `public/assets/modules/my_module/`.

Обращение из шаблона:

```blade
<img src="/assets/modules/my_module/logo.png" alt="Logo">
<link rel="stylesheet" href="/assets/modules/my_module/style.css">
```

## Bootstrap 5

Темы используют [Bootstrap 5.3](https://getbootstrap.com/docs/5.3/). Доступны все его компоненты: grid, утилиты, карточки, модалки и т.д.

Поддерживается тёмная тема через `data-bs-theme="dark"` на теге `<html>`. Переключение работает через cookie `theme`.
