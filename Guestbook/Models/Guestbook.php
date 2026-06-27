<?php

declare(strict_types=1);

namespace Modules\Guestbook\Models;

use App\Casts\HtmlCast;
use App\Models\File;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\FileableTrait;
use App\Traits\SearchableTrait;
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
 * @property int                  $id
 * @property int                  $user_id
 * @property string               $text
 * @property string               $ip
 * @property string               $brow
 * @property string               $reply
 * @property int                  $edit_user_id
 * @property bool                 $active
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User                  $user
 * @property-read Collection<int, File> $files
 */
class Guestbook extends Model
{
    use FileableTrait;
    use ConvertVideoTrait;
    use SearchableTrait;
    use UploadTrait;

    /**
     * The table associated with the model.
     */
    protected $table = 'guestbook';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Morph name
     */
    public static string $morphName = 'guestbook';

    /**
     * Директория загрузки файлов
     */
    public string $uploadPath = '/uploads/guestbook';

    /**
     * Возвращает поля участвующие в поиске
     */
    public function searchableFields(): array
    {
        return ['text', 'reply'];
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
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'text'    => HtmlCast::class,
            'reply'   => HtmlCast::class,
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
     * Возвращает связь пользователя изменившего запись
     */
    public function editUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id')->withDefault();
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'guestbook-' . $this->id);
    }

    /**
     * Get reply
     */
    public function getReply(): HtmlString
    {
        return renderHtml($this->reply);
    }

    /**
     * Удаление записи и загруженных файлов
     */
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
