<?php

declare(strict_types=1);

namespace Modules\UserLocation\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Date;

/**
 * Class UserLocation
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $path
 * @property string $title
 * @property Date   $created_at
 */
class UserLocation extends BaseModel
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

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
            'created_at' => 'datetime',
        ];
    }
}
