<?php

declare(strict_types=1);

namespace Modules\Offer\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ModuleSettingController;

class OfferSettingController extends ModuleSettingController
{
    protected string $view = 'offer::admin/settings/_offers';

    protected string $route = 'offer.settings';
}
