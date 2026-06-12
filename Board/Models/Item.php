<?php

declare(strict_types=1);

namespace Modules\Board\Models;

use App\Casts\HtmlCast;
use App\Models\File;
use App\Models\User;
use App\Traits\ConvertVideoTrait;
use App\Traits\FeedableTrait;
use App\Traits\FileableTrait;
use App\Traits\SearchableTrait;
use App\Traits\SortableTrait;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Class Item
 *
 * @property int    $id
 * @property int    $board_id
 * @property string $title
 * @property string $text
 * @property int    $user_id
 * @property int    $price
 * @property string $phone
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $expires_at
 * @property bool   $active
 * @property int    $visits
 * @property-read User                  $user
 * @property-read Board                 $category
 * @property-read Collection<int, File> $files
 */
class Item extends Model
{
    use FileableTrait;
    use FeedableTrait;
    use SearchableTrait;
    use SortableTrait;
    use ConvertVideoTrait;
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
    public string $uploadPath = '/uploads/boards';

    /**
     * Morph name
     */
    public static string $morphName = 'items';

    /**
     * Counting field
     */
    public string $countingField = 'visits';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'active'  => 'bool',
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
            'date'  => ['field' => 'updated_at', 'label' => __('main.date')],
            'price' => ['field' => 'price', 'label' => __('main.cost')],
            'name'  => ['field' => 'title', 'label' => __('main.title')],
        ];
    }

    /**
     * Scope a query to only include active records.
     */
    #[Scope]
    protected function active(Builder $query, bool $active = true): void
    {
        $query->where('active', $active);
    }

    /**
     * Возвращает связь пользователя
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /**
     * Возвращает категорию объявлений
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id')->withDefault();
    }

    /**
     * Возвращает путь к первому файлу
     */
    public function getFirstImage(): HtmlString
    {
        $image = $this->files->first();

        $path = $image->path ?? null;

        if ($path) {
            return new HtmlString('<img src="' . e($path) . '" alt="' . e($this->title) . '" class="img-fluid">');
        }

        return new HtmlString('<div class="text-center text-secondary py-3"><i class="fa fa-image fa-5x"></i></div>');
    }

    /**
     * Get text
     */
    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'item-' . $this->id);
    }

    /**
     * Удаление объявления и загруженных файлов
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
