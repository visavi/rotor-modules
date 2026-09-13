# Свои страницы

Чтобы добавить на сайт страницу, роут и контроллер писать не нужно: достаточно положить
blade-файл в один из двух каталогов. Ядро отдаёт такие страницы само.

| Раздел | Каталог | Адрес | Вложенность |
|---|---|---|---|
| `/files` | `resources/views/files/` | `/files/<путь>` | любой глубины |
| `/pages` | `resources/views/main/` | `/pages/<имя>` | один уровень |

Оба раздела работают одинаково и отличаются только формой адреса.

## Быстрый старт

```
resources/views/files/library/index.blade.php   →  /files/library
resources/views/files/library/rules.blade.php   →  /files/library/rules
resources/views/main/about.blade.php            →  /pages/about
```

Внутри файла — обычный blade: html, php, хелперы ядра, хуки. Оборачивать в layout
не нужно, ядро подставит его само.

Имя файла проверяется по маске: для `/files` — латиница, цифры, дефис, подчёркивание
и слеш, для `/pages` — то же без слеша. Файл `index` можно не писать: `/files/library`
и `/files/library/index` — один адрес.

## Блоки страницы

```blade
@section('title', 'Новый заголовок страницы')
```

Значение попадает в `<title>` и в заголовок `<h1>`. Если заголовок должен отличаться
от `title`, задаётся блок `header`:

```blade
@section('header')
    <h1>Измененное название страницы</h1>
@stop
```

Навигация и описание задаются так же:

```blade
@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">Активная страница</li>
        </ol>
    </nav>
@stop

@section('description', 'Описание страницы')
```

## Чтобы страницы пережили обновление

`resources/views` перезаписывается при обновлении ядра. Каталог `resources/custom`
не трогается и стоит первым в поиске шаблонов, поэтому страницы лучше класть туда —
адреса те же:

```
resources/custom/views/files/library/index.blade.php  →  /files/library
resources/custom/views/main/about.blade.php           →  /pages/about
```

Тем же способом переопределяется страница ядра: файл
`custom/views/main/index.blade.php` заменит страницу «Информация», а оригинал
останется нетронутым. Подробнее — «[Свои правки](/docs/rotor-custom)».

## Страница без меню и футера

Служебным страницам обвязка не нужна. Для них есть `layout_simple` — он наследует
макет активной темы, поэтому стили подключаются сами, а шапка, меню и футер гасятся:

```blade
@extends('layout_simple')

@section('title', 'Технические работы')

@section('content')
    <div class="container my-4">Скоро вернёмся</div>
@stop
```

Так устроены страницы блокировки по IP и закрытого сайта.

## Правка из админки

Создавать, править и удалять эти файлы можно прямо на сайте — модуль
«Редактор файлов» (AdminPanel → Редактор файлов). Там же поиск по коду и
редактор переводов.
