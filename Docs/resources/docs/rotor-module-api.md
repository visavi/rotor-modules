# API модулей (Registry)

`App\Classes\Registry` — реестр возможностей, через который модули сообщают ядру о своих типах контента и подписываются на события. Регистрация выполняется в `hooks.php` модуля.

В отличие от [хуков](/docs/rotor-hooks), которые вставляют HTML в шаблоны, Registry регистрирует поведение: участие в поиске и ленте, обработку жалоб, очистку данных при удалении пользователя и т.д.

## Регистраторы типов

Помечают morph-тип как поддерживающий ту или иную возможность. Обычно вызываются автоматически из секции `models` файла `module.php` — вручную нужны редко:

```php
use App\Classes\Registry;

Registry::fileType(string $morphName);                // тип принимает файлы
Registry::mediaType(string $morphName);               // тип принимает фото/видео
Registry::ratingType(string $morphName);              // тип поддерживает рейтинг
Registry::spamType(string $morphName, string $label); // тип — источник жалоб на спам
Registry::label(string $morphName, string $label);    // отображаемое название типа
Registry::feed(string $class, array $config);         // запись в ленте: ['view' => '', 'with' => [], 'scope' => ?Closure]
Registry::search(string $class, string $view, array $with = []); // полнотекстовый поиск
```

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

### pollResolver — где искать голосование

Для моделей, у которых голосование привязано не к самой записи, а к связанной. Возвращает морф-имя и id связанной записи либо `null`:

```php
Registry::pollResolver(Topic::class, function (Topic $topic): ?array {
    if (! $topic->last_post_id) {
        return null;
    }

    return [Post::$morphName, $topic->last_post_id];
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
