<?php

declare(strict_types=1);

namespace Modules\Template\Models;

use App\Casts\HtmlCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $title
 * @property string $text
 * @property int    $created_at
 */
class Template extends Model
{
    protected $table = 'templates';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'text' => HtmlCast::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function getText(): HtmlString
    {
        return renderHtml($this->text, 'template-' . $this->id);
    }
}
