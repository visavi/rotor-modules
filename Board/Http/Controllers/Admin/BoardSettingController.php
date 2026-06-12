<?php

declare(strict_types=1);

namespace Modules\Board\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class BoardSettingController extends ModuleSettingController
{
    protected string $view = 'board::admin/settings/_boards';

    protected string $route = 'board.settings';
}
