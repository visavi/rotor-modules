<?php

declare(strict_types=1);

namespace Modules\Rating\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class RatingSettingController extends ModuleSettingController
{
    protected string $view = 'rating::admin/settings/_ratings';

    protected string $route = 'rating.settings';
}
