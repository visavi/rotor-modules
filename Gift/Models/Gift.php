<?php

declare(strict_types=1);

namespace Modules\Gift\Models;

use App\Models\BaseModel;

/**
 * Class Gift
 *
 * @property int id
 * @property string name
 * @property string path
 * @property int price
 * @property int created_at
 */
class Gift extends BaseModel
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

    /**
     * Get config
     *
     * @param string|null $name
     *
     * @return mixed|null
     */
    public static function getConfig(?string $name = null)
    {
        $config = include base_path('modules/Gift/module.php');

        return $name ? $config[$name] ?? null : $config;
    }
}
