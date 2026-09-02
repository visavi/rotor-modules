<?php

declare(strict_types=1);

namespace Modules\Share\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class ShareSettingController extends ModuleSettingController
{
    protected string $view = 'share::admin/settings/_share';

    protected string $route = 'share.settings';
}
