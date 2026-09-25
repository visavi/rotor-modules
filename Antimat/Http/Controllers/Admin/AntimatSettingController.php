<?php

declare(strict_types=1);

namespace Modules\Antimat\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class AntimatSettingController extends ModuleSettingController
{
    protected string $view = 'antimat::admin/settings/_antimat';

    protected string $route = 'antimat.settings';
}
