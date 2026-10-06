<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Casts\TextCast;
use App\Models\User;
use App\Traits\CappableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Topic
 *
 * @property int                  $id
 * @property int                  $forum_id
 * @property string               $title
 * @property int                  $user_id
 * @property int                  $closed
 * @property int                  $locked
 * @property int                  $count_posts
 * @property int                  $visits
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable      $created_at
 * @property string|null          $moderators
 * @property string               $note
 * @property int                  $last_post_id
 * @property int                  $close_user_id
 * @property int|null             $bookmark_posts
 * @property bool                 $isModer
 * @property-read User                      $user
 * @property-read Post                      $lastPost
 * @property-read Forum                     $forum
 * @property-read Collection<int, Post>     $posts
 * @property-read Collection<int, Bookmark> $bookmarks
 * @property-read Vote                      $vote
 * @property Collection<int, User> $curators
 */
class Topic extends Model
{
    use SearchableTrait;
    use SortableTrait;
    use CappableTrait;

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Counting field
     */
    public string $countingField = 'visits';

    /**
     * Morph name
     */
    public static string $morphName = 'topics';

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['title'];
    }

    /**
     * Возвращает список сортируемых полей
     */
    protected static function sortableFields(): array
    {
        return [
            'date'   => ['field' => 'updated_at', 'label' => __('main.date')],
            'visits' => ['field' => 'visits', 'label' => __('main.views')],
            'posts'  => ['field' => 'count_posts', 'label' => __('main.messages')],
        ];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'title'      => TextCast::class,
            'user_id'    => 'int',
            'closed'     => 'bool',
            'locked'     => 'bool',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает сообщения
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'topic_id');
    }

    /**
     * Возвращает закладки
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'topic_id');
    }

    /**
     * Возвращает голосование
     */
    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class, 'topic_id')->withDefault();
    }

    /**
     * Возвращает последнее сообщение
     */
    public function lastPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_post_id')->withDefault();
    }

    /**
     * Возвращает раздел форума
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id')->withDefault();
    }

    /**
     * Возвращает связь пользователей
     */
    public function closeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'close_user_id')->withDefault();
    }

    /**
     * Ссылка на страницу темы, при наличии — сразу на последнее сообщение.
     * Последняя страница известна по счётчику, поэтому без pid: тот
     * считает страницу и делает лишний редирект (боты платят двумя запросами)
     */
    public function getViewUrl(bool $absolute = true): string
    {
        $url = route('topics.topic', array_filter(['id' => $this->id, 'page' => self::pageOf($this->count_posts)]), $absolute);

        return $this->last_post_id ? $url . '#post_' . $this->last_post_id : $url;
    }

    /**
     * Страница темы, на которой стоит сообщение с этим порядковым номером (null для первой)
     */
    public static function pageOf(int $position): ?int
    {
        $page = (int) ceil($position / max(1, (int) setting('forumpost')));

        return $page > 1 ? $page : null;
    }

    /**
     * Путь до раздела темы
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function getBreadcrumbs(bool $absolute = true): array
    {
        $breadcrumbs = [
            ['title' => __('forum::forums.forums'), 'url' => route('forums.index', [], $absolute)],
        ];

        if ($this->forum->parent->id) {
            $breadcrumbs[] = [
                'title' => $this->forum->parent->title,
                'url'   => route('forums.forum', ['id' => $this->forum->parent->id], $absolute),
            ];
        }

        if ($this->forum->id) {
            $breadcrumbs[] = [
                'title' => $this->forum->title,
                'url'   => route('forums.forum', ['id' => $this->forum->id], $absolute),
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Проверяет, является ли пользователь модератором темы
     */
    public function isModerator(User $user): bool
    {
        return in_array($user->login, explode(',', (string) $this->moderators), true);
    }

    /**
     * Удаление темы и связанных данных
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            // Удаление голосования
            $this->vote->delete();

            // Удаление закладок
            $this->bookmarks->each(static function (Bookmark $bookmark) {
                $bookmark->delete();
            });

            // Удаление сообщений
            $this->posts->each(static function (Post $post) {
                $post->delete();
            });

            return parent::delete();
        });
    }

    /**
     * Возвращает иконку в зависимости от статуса
     */
    public function getIcon(): string
    {
        if ($this->closed) {
            $icon = 'fa-lock';
        } elseif ($this->locked) {
            $icon = 'fa-thumbtack';
        } else {
            $icon = 'fa-folder-open';
        }

        return $icon;
    }

    /**
     * Генерирует постраничную навигацию для форума
     */
    public function pagination(string $url = '/topics'): ?HtmlString
    {
        if (! $this->count_posts) {
            return null;
        }

        $pages = [];
        $link = $url . '/' . $this->id;

        $pg_cnt = self::pageOf($this->count_posts) ?? 1;

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $pg_cnt) {
                $pages[] = [
                    'name' => $i,
                    'url'  => $i > 1 ? $link . '?page=' . $i : $link,
                ];
            }
        }

        if ($pg_cnt > 5) {
            if ($pg_cnt > 6) {
                $pages[] = [
                    'separator' => true,
                    'name'      => ' ... ',
                ];
            }

            $pages[] = [
                'name' => $pg_cnt,
                'url'  => $link . '?page=' . $pg_cnt,
            ];
        }

        return new HtmlString(view('forum::forums/_pagination', compact('pages'))->render());
    }

    /**
     * Пересчет темы
     */
    public function restatement(): void
    {
        $lastPost = Post::query()
            ->where('topic_id', $this->id)
            ->orderByDesc('id')
            ->first();

        $countPosts = Post::query()->where('topic_id', $this->id)->count();

        $this->update([
            'count_posts'  => $countPosts,
            'last_post_id' => $lastPost->id ?? 0,
        ]);

        $this->forum->restatement();
    }

    /**
     * Get count posts
     */
    public function getCountPosts(): HtmlString
    {
        $newPosts = null;
        if ($this->bookmark_posts && $this->count_posts > $this->bookmark_posts) {
            $newPosts = ' <span style="color:#00aa00">+' . ($this->count_posts - $this->bookmark_posts) . '</span>';
        }

        return new HtmlString($this->count_posts . $newPosts);
    }
}
