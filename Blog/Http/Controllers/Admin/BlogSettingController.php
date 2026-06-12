<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class BlogSettingController extends ModuleSettingController
{
    protected string $view = 'blog::admin/settings/_blogs';

    protected string $route = 'blog.settings';
}
