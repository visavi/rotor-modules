<?php

declare(strict_types=1);

namespace Modules\Counter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Counter31
 *
 * @property int    $id
 * @property string $period
 * @property int    $hosts
 * @property int    $hits
 */
class Counter31 extends Model
{
    /** @use HasFactory<\Modules\Counter\Tests\Factories\Counter31Factory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'counters31';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];
}
