<?php

declare(strict_types=1);

namespace Modules\Gift\Http\Controllers;

use App\Http\Controllers\Admin\ModuleSettingController;

class GiftSettingController extends ModuleSettingController
{
    protected string $view = 'gift::admin/settings/_gifts';

    protected string $route = 'gift.settings';
}
