<?php

declare(strict_types=1);

namespace Modules\Wall\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Wall\Models\Wall;

class WallObserver
{
    /**
     * Handle the Wall "created" event.
     */
    public function created(Wall $wall): void
    {
        Cache::forget('wall_count_' . $wall->user_id);
    }

    /**
     * Handle the Wall "deleted" event.
     */
    public function deleted(Wall $wall): void
    {
        Cache::forget('wall_count_' . $wall->user_id);
    }
}
