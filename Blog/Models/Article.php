<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\File;
use App\Models\Poll;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\FeedableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Class Article
 *
 * @property int    $id
 * @property int    $category_id
 * @property int    $user_id
 * @property string $title
 * @property string $slug
 * @property string $text
 * @property int    $rating
 * @property int    $visits
 * @property int    $count_comments
 * @property int    $created_at
 * @property bool   $active
 * @property bool   $draft
 * @property Date   $published_at
 * @property-read Collection<File>    $files
 * @property-read Collection<Comment> $comments
 * @property-read Collection<Poll>    $polls
 * @property-read Poll                $poll
 * @property-read Blog                $category
 */
class Article extends Model
{
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;
    use ConvertVideoTrait;
    use UploadTrait;

    public $timestamps = false;

    protected $guarded = [];

    public string $uploadPath = '/uploads/articles';

    public string $countingField = 'visits';

    public static string $morphName = 'articles';

    protected function casts(): array
    {
        return [
            'active'       => 'bool',
            'published_at' => 'datetime',
            'user_id'      => 'int',
            'text'         => HtmlCast::class,
        ];
    }

    public function searchableFields(): array
    {
        return ['title', 'text'];
    }

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

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => str_replace(['%id%', '%slug%'], [$this->id, $value], setting('slug_template')),
            set: fn ($value) => Str::slug($this->title),
        );
    }

    #[Scope]
    protected function active(Builder $query, bool $active = true): void
    {
        $query->where('active', $active);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'relate')->with('relate');
    }

    public function lastComments(int $limit = 15): HasMany
    {
        return $this->hasMany(Comment::class, 'relate_id')
            ->where('relate_type', self::$morphName)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->limit($limit);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'category_id')->withDefault();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'relate')
            ->orderBy('created_at');
    }

    public function getMedia(): Collection
    {
        return $this->files->filter(static fn (File $f) => $f->isImage() || $f->isVideo());
    }

    public function getDetachedMedia(): Collection
    {
        return $this->getMedia()->reject(fn (File $f) => str_contains($this->text ?? '', $f->path));
    }

    public function polls(): MorphMany
    {
        return $this->MorphMany(Poll::class, 'relate');
    }

    public function poll(): MorphOne
    {
        return $this->morphOne(Poll::class, 'relate')
            ->where('user_id', getUser('id'));
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id')
            ->withPivot('sort')
            ->orderBy('article_tags.sort');
    }

    public function getFirstImage(): ?HtmlString
    {
        $image = $this->files->first();

        if (! $image) {
            return null;
        }

        return new HtmlString('<img src="' . $image->path . '" atl="' . $this->title . '" class="card-img-top">');
    }

    public function isNew(): bool
    {
        return $this->created_at > strtotime('-3 day');
    }

    public function isPublished(): bool
    {
        return $this->published_at === null || ! $this->published_at->isFuture();
    }

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'article-' . $this->id);
    }

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
