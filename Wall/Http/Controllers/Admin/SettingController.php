<?php

declare(strict_types=1);

namespace Modules\Wall\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class SettingController extends ModuleSettingController
{
    protected string $view = 'wall::admin/settings/index';

    protected string $route = 'wall.settings';
}
