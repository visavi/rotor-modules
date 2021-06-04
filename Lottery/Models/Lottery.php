<?php

declare(strict_types=1);

namespace Modules\Lottery\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Lottery
 *
 * @property int id
 * @property string day
 * @property int amount
 * @property int number
 */
class Lottery extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lottery';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Возвращает связь c участниками
     *
     * @return HasMany
     */
    public function lotteryUsers(): HasMany
    {
        return $this->hasMany(LotteryUser::class, 'lottery_id');
    }

    /**
     * Get config
     *
     * @param string|null $name
     *
     * @return mixed|null
     */
    public static function getConfig(?string $name = null)
    {
        $config = include base_path('modules/Lottery/module.php');

        return $name ? $config[$name] ?? null : $config;
    }
}
