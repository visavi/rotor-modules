<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class NewsSettingController extends ModuleSettingController
{
    protected string $view = 'news::admin/settings/_news';

    protected string $route = 'news.settings';
}
