<?php

declare(strict_types=1);

namespace Modules\Load\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class LoadSettingController extends ModuleSettingController
{
    protected string $view = 'load::admin/settings/_loads';

    protected string $route = 'load.settings';
}
