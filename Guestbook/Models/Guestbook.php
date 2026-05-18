<?php

declare(strict_types=1);

namespace Modules\Guestbook\Models;

use App\Casts\HtmlCast;
use App\Models\File;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\SearchableTrait;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $text
 * @property string $ip
 * @property string $brow
 * @property int    $created_at
 * @property string $reply
 * @property int    $edit_user_id
 * @property bool   $active
 * @property int    $updated_at
 * @property-read Collection<File> $files
 */
class Guestbook extends Model
{
    use ConvertVideoTrait;
    use SearchableTrait;
    use UploadTrait;

    protected $table = 'guestbook';

    public $timestamps = false;

    protected $guarded = [];

    public static string $morphName = 'guestbook';

    public string $uploadPath = '/uploads/guestbook';

    public function searchableFields(): array
    {
        return ['text', 'reply'];
    }

    #[Scope]
    protected function active(Builder $query, bool $type = true): void
    {
        $query->where('active', $type);
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

    public function editUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id')->withDefault();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'relate')
            ->orderBy('created_at');
    }

    public function getFiles(): Collection
    {
        return $this->files->filter(static fn (File $f) => ! $f->isImage() && ! $f->isVideo());
    }

    public function getMedia(): Collection
    {
        return $this->files->filter(static fn (File $f) => $f->isImage() || $f->isVideo());
    }

    public function getDetachedMedia(): Collection
    {
        return $this->getMedia()->reject(fn (File $f) => str_contains($this->text ?? '', $f->path));
    }

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'guestbook-' . $this->id);
    }

    public function getReply(): HtmlString
    {
        return renderHtml($this->reply);
    }

    public function delete(): ?bool
    {
        return DB::transaction(function () {
            $this->files->each(static function (File $file) {
                $file->delete();
            });

            return parent::delete();
        });
    }
}
