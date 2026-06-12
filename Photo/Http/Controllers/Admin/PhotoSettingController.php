<?php

declare(strict_types=1);

namespace Modules\Photo\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class PhotoSettingController extends ModuleSettingController
{
    protected string $view = 'photo::admin/settings/_photos';

    protected string $route = 'photo.settings';
}
