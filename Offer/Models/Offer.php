<?php

declare(strict_types=1);

namespace Modules\Offer\Models;

use App\Casts\HtmlCast;
use App\Models\Comment;
use App\Models\Poll;
use App\Models\User;
use App\Traits\FeedableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * @property int    $id
 * @property string $type
 * @property string $title
 * @property string $text
 * @property int    $user_id
 * @property int    $rating
 * @property int    $created_at
 * @property string $status
 * @property int    $count_comments
 * @property int    $closed
 * @property string $reply
 * @property int    $reply_user_id
 * @property int    $updated_at
 * @property-read Collection<Comment> $comments
 * @property-read Collection<Poll>    $polls
 * @property-read Poll                $poll
 * @property-read User                $replyUser
 */
class Offer extends Model
{
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;

    public const string DONE = 'done';
    public const string WAIT = 'wait';
    public const string CANCEL = 'cancel';
    public const string PROCESS = 'process';

    public const array STATUSES = [
        self::DONE,
        self::WAIT,
        self::CANCEL,
        self::PROCESS,
    ];

    public const string OFFER = 'offer';
    public const string ISSUE = 'issue';

    public const array TYPES = [
        self::OFFER,
        self::ISSUE,
    ];

    public $timestamps = false;

    protected $guarded = [];

    public static string $morphName = 'offers';

    public function searchableFields(): array
    {
        return ['title', 'text', 'reply'];
    }

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

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'text'    => HtmlCast::class,
            'reply'   => HtmlCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'relate')
            ->with('relate', 'user');
    }

    public function replyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_user_id')->withDefault();
    }

    public function lastComments(int $limit = 15): HasMany
    {
        return $this->hasMany(Comment::class, 'relate_id')
            ->where('relate_type', self::$morphName)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->limit($limit);
    }

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'offer-' . $this->id);
    }

    public function getReply(): HtmlString
    {
        return renderHtml($this->reply);
    }

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
