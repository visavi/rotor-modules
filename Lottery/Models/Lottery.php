<?php

declare(strict_types=1);

namespace Modules\Lottery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Lottery
 *
 * @property int    $id
 * @property string $day
 * @property int    $amount
 * @property int    $number
 */
class Lottery extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'lottery';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Возвращает связь с участниками
     */
    public function lotteryUsers(): HasMany
    {
        return $this->hasMany(LotteryUser::class, 'lottery_id');
    }

    /**
     * Get config
     */
    public static function getConfig(?string $name = null): mixed
    {
        $config = include base_path('modules/Lottery/module.php');

        return $name ? $config[$name] ?? null : $config;
    }
}
