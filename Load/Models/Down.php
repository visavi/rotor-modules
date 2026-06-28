<?php

declare(strict_types=1);

namespace Modules\Load\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\File;
use App\Models\Poll;
use App\Models\User;
use App\Traits\AddFileToArchiveTrait;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Down
 *
 * @property int                  $id
 * @property int                  $category_id
 * @property string               $title
 * @property string               $text
 * @property int                  $user_id
 * @property int                  $count_comments
 * @property int                  $rating
 * @property int                  $loads
 * @property bool                 $active
 * @property array                $links
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User                       $user
 * @property-read Collection<int, File>      $files
 * @property-read Collection<int, Comment>   $comments
 * @property-read Collection<int, Poll>      $polls
 * @property-read Poll                       $poll
 * @property-read Load                       $category
 */
class Down extends Model
{
    use AddFileToArchiveTrait;
    use CommentableTrait;
    use ConvertVideoTrait;
    use FeedableTrait;
    use FileableTrait;
    use PollableTrait;
    use SearchableTrait;
    use SortableTrait;
    use UploadTrait;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Директория загрузки файлов
     */
    public string $uploadPath = '/uploads/files';

    /**
     * Counting field
     */
    public string $countingField = 'loads';

    /**
     * Morph name
     */
    public static string $morphName = 'downs';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'active'  => 'bool',
            'links'   => 'array',
            'user_id' => 'int',
            'text'    => HtmlCast::class,
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
            'loads'    => ['field' => 'loads', 'label' => __('main.downloads')],
            'name'     => ['field' => 'title', 'label' => __('main.title')],
            'rating'   => ['field' => 'rating', 'label' => __('main.rating')],
            'comments' => ['field' => 'count_comments', 'label' => __('main.comments')],
        ];
    }

    /**
     * Scope a query to only include active records.
     */
    #[Scope]
    protected function active(Builder $query, bool $type = true): void
    {
        $query->where('active', $type);
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает категорию загрузок
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Load::class, 'category_id')->withDefault();
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'down-' . $this->id);
    }

    /**
     * Get text for share (RSS, API)
     */
    public function getShareText(): string
    {
        return absolutizeUrls((string) renderHtml($this->text));
    }

    /**
     * Is new file
     */
    public function isNew(): bool
    {
        return $this->created_at->gt(now()->subDays(3));
    }

    /**
     * Удаление загрузки и загруженных файлов
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
