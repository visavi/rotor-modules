<?php

declare(strict_types=1);

namespace Modules\Privacy\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Согласие пользователя на обработку персональных данных
 *
 * Хранится как доказательство: какую версию текста, когда и с какого IP принял пользователь
 *
 * @property int             $user_id
 * @property int             $text_id
 * @property string          $ip
 * @property CarbonImmutable $created_at
 */
class PrivacyConsent extends Model
{
    /** @use HasFactory<\Modules\Privacy\Tests\Factories\PrivacyConsentFactory> */
    use HasFactory;

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'int',
            'text_id' => 'int',
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
     * Версия текста, которую принял пользователь
     */
    public function text(): BelongsTo
    {
        return $this->belongsTo(PrivacyText::class, 'text_id');
    }
}
