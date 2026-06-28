<?php

declare(strict_types=1);

namespace Modules\Offer\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\Poll;
use App\Models\User;
use App\Traits\CommentableTrait;
use App\Traits\FeedableTrait;
use App\Traits\PollableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * @property int                  $id
 * @property string               $type
 * @property string               $title
 * @property string               $text
 * @property int                  $user_id
 * @property int                  $rating
 * @property string               $status
 * @property int                  $count_comments
 * @property bool                 $closed
 * @property string               $reply
 * @property int                  $reply_user_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User                     $user
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, Poll>    $polls
 * @property-read Poll                     $poll
 * @property-read User                     $replyUser
 */
class Offer extends Model
{
    use CommentableTrait;
    use PollableTrait;
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;

    public const string DONE = 'done';
    public const string WAIT = 'wait';
    public const string CANCEL = 'cancel';
    public const string PROCESS = 'process';

    /**
     * Статусы
     */
    public const array STATUSES = [
        self::DONE,
        self::WAIT,
        self::CANCEL,
        self::PROCESS,
    ];

    public const string OFFER = 'offer';
    public const string ISSUE = 'issue';

    /**
     * Типы
     */
    public const array TYPES = [
        self::OFFER,
        self::ISSUE,
    ];

    /**
     * The name of the "updated at" column.
     *
     * updated_at = время правки/ответа, ставится вручную. Авто-таймстамп отключён,
     * чтобы инкременты count_comments/rating не сбивали дату официального ответа.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Morph name
     */
    public static string $morphName = 'offers';

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['title', 'text', 'reply'];
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
            'status'   => ['field' => 'status', 'label' => __('main.status')],
        ];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id'    => 'int',
            'closed'     => 'bool',
            'text'       => HtmlCast::class,
            'reply'      => HtmlCast::class,
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
     * Возвращает связь пользователей
     */
    public function replyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_user_id')->withDefault();
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'offer-' . $this->id);
    }

    /**
     * Get reply
     */
    public function getReply(): HtmlString
    {
        return renderHtml($this->reply);
    }

    /**
     * Возвращает статус записи
     */
    public function getStatus(): HtmlString
    {
        $status = match ($this->status) {
            'process' => '<span class="fw-bold text-primary"><i class="fa fa-spinner"></i> ' . __('offer::offers.process') . '</span>',
            'done'    => '<span class="fw-bold text-success"><i class="fa fa-check-circle"></i> ' . __('offer::offers.done') . '</span>',
            'cancel'  => '<span class="fw-bold text-danger"><i class="fa fa-times-circle"></i> ' . __('offer::offers.cancel') . '</span>',
            default   => '<span class="fw-bold text-warning"><i class="fa fa-question-circle"></i> ' . __('offer::offers.wait') . '</span>',
        };

        return new HtmlString($status);
    }

    /**
     * Удаление записи
     */
    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->polls()->delete();

            $this->comments->each(static function (Comment $comment) {
                $comment->delete();
            });

            return parent::delete();
        });
    }
}
