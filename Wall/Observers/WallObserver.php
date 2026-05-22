<?php

declare(strict_types=1);

namespace Modules\Wall\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Wall\Models\Wall;

class WallObserver
{
    public function created(Wall $wall): void
    {
        Cache::forget('wall_count_' . $wall->user_id);
    }

    public function deleted(Wall $wall): void
    {
        Cache::forget('wall_count_' . $wall->user_id);
    }
}
