<?php

declare(strict_types=1);

namespace Modules\Advert\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class SettingController extends ModuleSettingController
{
    protected string $view = 'advert::admin/settings/index';

    protected string $route = 'advert.settings';
}
