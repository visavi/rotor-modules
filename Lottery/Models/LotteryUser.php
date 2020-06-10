<?php

declare(strict_types=1);

namespace Modules\Lottery\Models;

use App\Models\BaseModel;

/**
 * Class LotteryUser
 *
 * @property int id
 * @property int lottery_id
 * @property int user_id
 * @property int number
 * @property int created_at
 */
class LotteryUser extends BaseModel
{
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
}
