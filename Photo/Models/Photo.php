<?php

declare(strict_types=1);

namespace Modules\Photo\Models;

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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Photo
 *
 * @property int                  $id
 * @property int                  $user_id
 * @property string               $title
 * @property string               $text
 * @property int                  $rating
 * @property bool                 $closed
 * @property int                  $count_comments
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User                     $user
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, File>    $files
 * @property-read Collection<int, Poll>    $polls
 * @property-read Poll                     $poll
 */
class Photo extends Model
{
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
    public string $uploadPath = '/uploads/photos';

    /**
     * Morph name
     */
    public static string $morphName = 'photos';

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
            'comments' => ['field' => 'count_comments', 'label' => __('main.comments')],
            'rating'   => ['field' => 'rating', 'label' => __('main.rating')],
            'name'     => ['field' => 'title', 'label' => __('main.title')],
        ];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'closed'  => 'bool',
            'text'    => HtmlCast::class,
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
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'photo-' . $this->id);
    }

    /**
     * Удаление фото и загруженных файлов
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
