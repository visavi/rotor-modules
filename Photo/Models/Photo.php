<?php

declare(strict_types=1);

namespace Modules\Photo\Models;

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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Photo
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $title
 * @property string $text
 * @property int    $created_at
 * @property int    $rating
 * @property int    $closed
 * @property int    $count_comments
 * @property-read Collection<Comment> $comments
 * @property-read Collection<File>    $files
 * @property-read Collection<Poll>    $polls
 * @property-read Poll                $poll
 */
class Photo extends Model
{
    use ConvertVideoTrait;
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;
    use UploadTrait;

    public $timestamps = false;

    protected $guarded = [];

    public string $uploadPath = '/uploads/photos';

    public static string $morphName = 'photos';

    public function searchableFields(): array
    {
        return ['title', 'text'];
    }

    protected static function sortableFields(): array
    {
        return [
            'date'     => ['field' => 'created_at', 'label' => __('main.date')],
            'comments' => ['field' => 'count_comments', 'label' => __('main.comments')],
            'rating'   => ['field' => 'rating', 'label' => __('main.rating')],
            'name'     => ['field' => 'title', 'label' => __('main.title')],
        ];
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'text'    => HtmlCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'relate')->with('relate');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'relate')
            ->orderBy('created_at');
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

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'photo-' . $this->id);
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
