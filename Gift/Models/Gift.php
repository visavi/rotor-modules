<?php

declare(strict_types=1);

namespace Modules\Gift\Models;

use App\Attributes\NotFoundMessage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Gift
 *
 * @property int             $id
 * @property string          $name
 * @property string          $path
 * @property int             $price
 * @property CarbonImmutable $created_at
 */
#[NotFoundMessage('gift::gifts.gift_not_found')]
class Gift extends Model
{
    /** @use HasFactory<\Modules\Gift\Tests\Factories\GiftFactory> */
    use HasFactory;

    /**
     * The name of the "updated at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Возвращает картинку подарка для вставки в уведомление
     */
    public function getImage(): string
    {
        return '<img src="' . e($this->path) . '" alt="' . e($this->name) . '">';
    }

    /**
     * Get config
     */
    public static function getConfig(?string $name = null): mixed
    {
        $config = include base_path('modules/Gift/module.php');

        return $name ? $config[$name] ?? null : $config;
    }
}
