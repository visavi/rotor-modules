<?php

declare(strict_types=1);

namespace Modules\News\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\File;
use App\Models\Poll;
use App\Models\User;
use App\Traits\CommentsTrait;
use App\Traits\ConvertVideoTrait;
use App\Traits\FeedableTrait;
use App\Traits\FilesTrait;
use App\Traits\PollsTrait;
use App\Traits\SearchableTrait;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * @property int    $id
 * @property string $title
 * @property string $text
 * @property int    $user_id
 * @property int    $created_at
 * @property int    $count_comments
 * @property bool   $closed
 * @property int    $top
 * @property-read User                     $user
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, File>    $files
 * @property-read Collection<int, Poll>    $polls
 * @property-read Poll                     $poll
 */
class News extends Model
{
    use CommentsTrait;
    use PollsTrait;
    use FilesTrait;
    use ConvertVideoTrait;
    use FeedableTrait;
    use SearchableTrait;
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
    public string $uploadPath = '/uploads/news';

    /**
     * Morph name
     */
    public static string $morphName = 'news';

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['title', 'text'];
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
        return renderHtml($this->text, 'news-' . $this->id);
    }

    /**
     * Возвращает иконку в зависимости от статуса
     */
    public function getIcon(): string
    {
        return $this->closed ? 'fa-lock' : 'fa-unlock';
    }

    /**
     * Удаление новости и загруженных файлов
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
