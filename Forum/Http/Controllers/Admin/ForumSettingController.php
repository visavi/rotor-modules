<?php

declare(strict_types=1);

namespace Modules\Forum\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class ForumSettingController extends ModuleSettingController
{
    protected string $view = 'forum::admin/settings/_forums';

    protected string $route = 'forum.settings';
}
