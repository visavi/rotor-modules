# Интеграция с ядром

`App\Support\Registry` — точка интеграции, через которую модули сообщают ядру о своих типах контента и подписываются на события. Регистрация выполняется в `hooks.php` модуля.

В отличие от [хуков](/docs/rotor-hooks), которые вставляют HTML в шаблоны, Registry регистрирует поведение: участие в поиске и ленте, обработку жалоб, очистку данных при удалении пользователя и т.д.

## Регистраторы типов

Помечают morph-тип как поддерживающий ту или иную возможность. Обычно вызываются автоматически из секции `models` файла `module.php` — вручную нужны редко:

```php
use App\Support\Registry;

Registry::fileType(string $morphName);                // тип принимает файлы
Registry::mediaType(string $morphName);               // тип принимает фото/видео
Registry::ratingType(string $morphName);              // тип поддерживает рейтинг
Registry::spamType(string $morphName);                // тип — источник жалоб на спам (метка берётся из labelTypes)
Registry::label(string $morphName, string $label);    // отображаемое название типа
Registry::feed(string $class, array $config);         // запись в ленте, ключи конфига — ниже
Registry::search(string $class, string $view, array $with = []); // полнотекстовый поиск
```

### Конфигурация ленты

| Ключ | Тип | Назначение |
|---|---|---|
| `view` | string | Шаблон записи для ленты сайта |
| `with` | array | Eager-загрузки |
| `scope` | ?Closure | Условия видимости записи (активность, срок), при необходимости join |
| `poll` | ?Closure | Возвращает `[$morphName, $id]`, если голосование привязано к связанной записи |
| `source` | ?Closure | Модель-носитель контента, если она отличается от записи ленты (тема форума → последний пост) |
| `api` | ?Closure | Доп. поля записи в `/api/feed`, возвращает массив |

Без `source` запись остаётся в ленте сайта, но в API отдаётся полями самой записи.

Связь `files` указывать в `with` не нужно: ядро добавляет её само, если модель использует `FileableTrait`. Иначе забытый ключ молча оставлял бы записи в `/api/feed` без вложений.

### Ссылка на запись

Ссылку на свою страницу модель отдаёт сама — метод используют `/api/feed`, уведомления, RSS и вёрстка:

```php
public function getViewUrl(bool $absolute = true): string
{
    return route('news.view', ['id' => $this->id], $absolute);
}
```

Метод необязателен: без него запись придёт в API с `url: null`.

### Путь до раздела

Хлебные крошки записи модель тоже отдаёт сама — их использует `/api/feed`, чтобы клиент показал раздел и мог в него перейти:

```php
/**
 * @return array<int, array{title: string, url: string}>
 */
public function getBreadcrumbs(bool $absolute = true): array
{
    $breadcrumbs = [
        ['title' => __('blog::blogs.blogs'), 'url' => route('blogs.index', [], $absolute)],
    ];

    if ($this->category) {
        $breadcrumbs[] = [
            'title' => $this->category->name,
            'url'   => route('blogs.blog', ['id' => $this->category->id], $absolute),
        ];
    }

    return $breadcrumbs;
}
```

Порядок — от корня раздела к записи, сама запись в крошки не входит. Категорию нужно указать в ключе `with` конфига ленты, иначе будет запрос на каждую запись. Метод необязателен: без него в API придёт `breadcrumbs: []`.

### API раздела

Свои API-роуты модуль объявляет в `routes.php` под группой `api`. Токен для чтения делать обязательным не нужно — лента публична, и переход из неё не должен упираться в 401:

```php
Route::middleware(['api', 'check.token.optional'])
    ->prefix('api')
    ->group(function () {
        Route::get('/offers/{id}', [OfferApiController::class, 'view']);
    });
```

Запись отдаётся вместе с комментариями: `data` — страница комментариев, сама запись уходит в `additional`. Комментарии готовит трейт ядра `HandlesApiComments`, он же обрабатывает `per_page`, `page` и `order`:

```php
use App\Traits\HandlesApiComments;

return CommentResource::collection($this->apiComments($offer, $request))
    ->additional(['offer' => OfferResource::make($offer)]);
```

Комментарии приходят плоским списком с `parent_id` и `depth` — дерево собирает клиент, иначе пагинация резала бы ветки ответов. Удалённые остаются заглушкой с `deleted: true`.

Голос текущего пользователя подмешивается в запрос записи join'ом по `polls` — в ресурсе он ложится в `vote.value`, а `vote.type` и `vote.id` клиент отправляет в `POST /api/rating`.

Список раздела отдаётся тем же ресурсом, что и одиночная запись, — клиент получает одинаковую структуру везде. Параметры `per_page`, `page` и `order` разбирает трейт `HandlesApiPagination` (входит в `HandlesApiComments`), сортировку берите из `getSorting()` модели, чтобы набор полей совпадал с сайтом:

```php
[, $orderBy] = Article::getSorting($request->input('sort', 'date'), $this->apiOrder($request, 'desc'));

$articles = Article::query()
    ->active()
    ->when($request->integer('category_id'), static fn ($query, $id) => $query->where('category_id', $id))
    ->orderBy(...$orderBy)
    ->paginate($this->apiPerPage($request));
```

Если раздел построен на категориях, добавьте и их список — клиенту нужно из чего строить навигацию.

Отдельных ручек на добавление комментариев модулю писать не нужно: их принимает ядро, `POST /api/comments` с `type` и `id` записи. Тип берётся из `$morphName` модели, а сама она попадает в список разрешённых автоматически, если использует `CommentableTrait`. Вложения тоже общие — `POST /api/files` с `type` записи; файлы, загруженные до создания записи (`id = 0`), привязываются к ней при сохранении.

Соответствие ключей `module.php` методам Registry:

| Ключ в `models` | Метод Registry |
|---|---|
| `'upload' => 'file'` | `fileType()` |
| `'upload' => 'media'` | `mediaType()` |
| `'rating' => true` | `ratingType()` |
| `'spam' => true` | `spamType()` |
| `'label' => '...'` | `label()` |
| `'feed' => [...]` | `feed()` |
| `'search' => [...]` | `search()` |

## Колбэки

Регистрируются в `hooks.php` вручную.

### complaint — обработчик жалобы

Получает id записи, возвращает модель и путь к ней:

```php
use Modules\Forum\Models\Post;

Registry::complaint(Post::$morphName, function (int $id, mixed $page): array {
    $model = Post::query()->find($id);
    $path = $model ? route('topics.topic', ['id' => $model->topic_id, 'pid' => $model->id], false) : null;

    return ['model' => $model, 'path' => $path];
});
```

### sitemap — страница в sitemap

Возвращает массив записей `['loc' => url, 'lastmod' => date]`:

```php
Registry::sitemap('topics', function (): array {
    return Topic::query()
        ->orderByDesc('created_at')
        ->limit(10000)
        ->get()
        ->map(fn (Topic $topic) => [
            'loc'     => route('topics.topic', ['id' => $topic->id]),
            'lastmod' => gmdate('c', $topic->created_at),
        ])
        ->all();
});
```

### onDeleteUser — очистка при удалении пользователя

Вызывается, когда пользователь удаляет свой аккаунт:

```php
use App\Models\User;

Registry::onDeleteUser(function (User $user): void {
    Bookmark::query()->where('user_id', $user->id)->delete();
});
```

### onAdminDeleteUser — удаление администратором

Дополнительно получает `Request` — в нём чекбоксы формы удаления. Свой чекбокс добавляется через `Hook::add('adminUserDeleteFields', ...)`:

```php
use Illuminate\Http\Request;

Registry::onAdminDeleteUser(function (User $user, Request $request): void {
    if ($request->boolean('deltopics')) {
        Topic::query()->where('user_id', $user->id)->get()
            ->each(static fn (Topic $topic) => $topic->delete());
    }
});

Hook::add('adminUserDeleteFields', static fn () => '<div class="form-check">
    <input type="checkbox" class="form-check-input" value="1" name="deltopics" id="deltopics">
    <label class="form-check-label" for="deltopics">' . __('users.forum_topics') . '</label>
</div>');
```

## Морф-имена

Каждая модель, участвующая в Registry, объявляет морф-имя:

```php
public static string $morphName = 'articles';
```

Ядро регистрирует его в `Relation::enforceMorphMap()`. Ограничения:

- максимум **20 символов** (ширина колонки `relate_type` в БД);
- имя попадает в записи БД — после релиза модуля менять его нельзя.

## См. также

- [Модули](/docs/rotor-modules) — структура и разработка модуля
- [Хуки](/docs/rotor-hooks) — вставка HTML в шаблоны ядра
- [Реестр модулей](/docs/rotor-module-registry) — распространение модулей
