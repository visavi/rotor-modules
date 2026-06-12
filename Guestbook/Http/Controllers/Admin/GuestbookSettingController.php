<?php

declare(strict_types=1);

namespace Modules\Guestbook\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class GuestbookSettingController extends ModuleSettingController
{
    protected string $view = 'guestbook::admin/settings/_guestbook';

    protected string $route = 'guestbook.settings';
}
