<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Casts\HtmlCast;
use App\Models\File;
use App\Models\Poll;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\FileableTrait;
use App\Traits\PollableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Post
 *
 * @property int    $id
 * @property int    $topic_id
 * @property int    $user_id
 * @property string $text
 * @property int    $rating
 * @property int    $created_at
 * @property string $ip
 * @property string $brow
 * @property int    $edit_user_id
 * @property int    $updated_at
 * @property-read User             $user
 * @property-read Collection<int, File> $files
 * @property-read Collection<int, Poll> $polls
 * @property-read Poll                  $poll
 * @property-read Topic                 $topic
 * @property-read User                  $editUser
 */
class Post extends Model
{
    use PollableTrait;
    use FileableTrait;
    use ConvertVideoTrait;
    use SearchableTrait;
    use SortableTrait;
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
    public string $uploadPath = '/uploads/forums';

    /**
     * Morph name
     */
    public static string $morphName = 'posts';

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['text'];
    }

    /**
     * Возвращает список сортируемых полей
     */
    protected static function sortableFields(): array
    {
        return [
            'date'   => ['field' => 'created_at', 'label' => __('main.date')],
            'rating' => ['field' => 'rating', 'label' => __('main.rating')],
        ];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
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
     * Возвращает связь пользователя
     */
    public function editUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id')->withDefault();
    }

    /**
     * Возвращает топик
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id')->withDefault();
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'post-' . $this->id);
    }

    /**
     * Get text for share (RSS, API)
     */
    public function getShareText(): string
    {
        return absolutizeUrls((string) renderHtml($this->text));
    }

    /**
     * Удаление поста и загруженных файлов
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->polls()->delete();

            $this->files->each(static function (File $file) {
                $file->delete();
            });

            return parent::delete();
        });
    }
}
