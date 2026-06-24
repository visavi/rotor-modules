<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\File;
use App\Models\Poll;
use App\Models\User;
use App\Traits\CommentableTrait;
use App\Traits\ConvertVideoTrait;
use App\Traits\FeedableTrait;
use App\Traits\FileableTrait;
use App\Traits\PollableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\UploadTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Class Article
 *
 * @property int              $id
 * @property int              $category_id
 * @property int              $user_id
 * @property string           $title
 * @property string           $slug
 * @property string           $text
 * @property int              $rating
 * @property int              $visits
 * @property int              $count_comments
 * @property int              $created_at
 * @property bool             $active
 * @property bool             $draft
 * @property ?CarbonImmutable $published_at
 * @property-read User                     $user
 * @property-read Collection<int, File>    $files
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, Poll>    $polls
 * @property-read Poll                     $poll
 * @property-read Blog                     $category
 */
class Article extends Model
{
    use CommentableTrait;
    use PollableTrait;
    use FileableTrait;
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;
    use ConvertVideoTrait;
    use UploadTrait;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Директория загрузки файлов
     */
    public string $uploadPath = '/uploads/articles';

    /**
     * Counting field
     */
    public string $countingField = 'visits';

    /**
     * Morph name
     */
    public static string $morphName = 'articles';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'active'       => 'bool',
            'published_at' => 'datetime',
            'user_id'      => 'int',
            'text'         => HtmlCast::class,
        ];
    }

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['title', 'text'];
    }

    /**
     * Возвращает список сортируемых полей
     */
    protected static function sortableFields(): array
    {
        return [
            'date'     => ['field' => 'created_at', 'label' => __('main.date')],
            'name'     => ['field' => 'title', 'label' => __('main.title')],
            'visits'   => ['field' => 'visits', 'label' => __('main.views')],
            'rating'   => ['field' => 'rating', 'label' => __('main.rating')],
            'comments' => ['field' => 'count_comments', 'label' => __('main.comments')],
        ];
    }

    /**
     * Get the slug
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => str_replace(['%id%', '%slug%'], [(string) $this->id, $value], setting('slug_template')),
            set: fn ($value) => Str::slug($this->title),
        );
    }

    /**
     * Scope a query to only include active records.
     */
    #[Scope]
    protected function active(Builder $query, bool $active = true): void
    {
        $query->where('active', $active);
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает связь категории блога
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'category_id')->withDefault();
    }

    /**
     * Tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id')
            ->withPivot('sort')
            ->orderBy('article_tags.sort');
    }

    /**
     * Возвращает путь к первому файлу
     */
    public function getFirstImage(): ?HtmlString
    {
        $image = $this->files->first();

        if (! $image) {
            return null;
        }

        return new HtmlString('<img src="' . $image->path . '" atl="' . $this->title . '" class="card-img-top">');
    }

    /**
     * Is new article
     */
    public function isNew(): bool
    {
        return $this->created_at > strtotime('-3 day');
    }

    /**
     * Is published
     */
    public function isPublished(): bool
    {
        return $this->published_at === null || ! $this->published_at->isFuture();
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'article-' . $this->id);
    }

    /**
     * Get text for share (RSS, API)
     */
    public function getShareText(): string
    {
        return absolutizeUrls((string) renderHtml($this->text));
    }

    /**
     * Удаление статьи и загруженных файлов
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->polls()->delete();

            $this->comments->each(static function (Comment $comment) {
                $comment->delete();
            });

            $this->files->each(static function (File $file) {
                $file->delete();
            });

            return parent::delete();
        });
    }
}
