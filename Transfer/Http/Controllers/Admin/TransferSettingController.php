<?php

declare(strict_types=1);

namespace Modules\Transfer\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class TransferSettingController extends ModuleSettingController
{
    protected string $view = 'transfer::admin/settings/_transfers';

    protected string $route = 'transfer.settings';
}
