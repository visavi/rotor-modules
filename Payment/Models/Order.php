<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Date;

/**
 * Class Order
 *
 * @property int id
 * @property int user_id
 * @property string type
 * @property int amount
 * @property string currency
 * @property Date created_at
 * @property Date updated_at
 */
class Order extends BaseModel
{
    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];
}
