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

Имя активной темы лежит в настройке `themes`:

```php
setting('themes')       // тема сайта, например 'default'
getAvailableThemes()    // список установленных тем
```

У пользователя может быть своя тема (`$user->themes`) — она имеет приоритет над
настройкой сайта. Подстановкой занимается middleware `ApplySettings`.

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

Тема — это папка с blade-файлами; она появляется в списке сразу, как только создана.

### Со сборкой

1. Скопируйте `resources/views/themes/default` с новым именем
2. Скопируйте `resources/themes/default` — исходники стилей темы
3. Добавьте точку входа `resources/themes/<имя>/js/app.js` в `input` в `vite.config.js`
4. Соберите: `npm run build`
5. Активируйте в AdminPanel → Настройки

### Без сборки

Не требует npm и правок `vite.config.js` — готовый пример лежит
в `resources/views/themes/simple`.

1. Создайте `resources/views/themes/<имя>` с `layout.blade.php`
2. Положите свои `style.css` и `app.js` в `public/themes/<имя>`
3. В layout подключите общие файлы ядра и свои:

```blade
@vite('resources/css/bootstrap.scss')
@vite('resources/themes/vendor.scss')
@vite('resources/js/main.js')
<link rel="stylesheet" href="{{ asset('/themes/<имя>/style.css') }}">
```

4. Активируйте в AdminPanel → Настройки

Оформление задаётся CSS-переменными Bootstrap (`--bs-primary` и другие), подробнее —
в разделе «Сборка ресурсов».

### Что обязан отдавать layout

```blade
@translation                 {{-- переводы для js --}}
@yield('navbar')             {{-- @includeIf('theme::navbar') --}}
@yield('sidebar')            {{-- @includeIf('theme::sidebar'), если он есть --}}
@yield('titlebar')
@yield('flash')
@yield('content')
@stack('styles')
@stack('scripts')            {{-- сюда попадают модалки языка и правки комментариев --}}
@hook('head') @hook('footer') @hook('contentStart') @hook('contentEnd')
```

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
