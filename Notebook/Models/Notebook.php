<?php

declare(strict_types=1);

namespace Modules\Notebook\Models;

use App\Casts\HtmlCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

/**
 * Class Notebook
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $text
 * @property int    $created_at
 */
class Notebook extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'text'    => HtmlCast::class,
        ];
    }

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'notebook-' . $this->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }
}
