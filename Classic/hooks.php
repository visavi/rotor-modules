<?php

use App\Classes\Hook;

view()->addLocation(base_path('modules/Classic/resources/views'));
app('translator')->addNamespace('classic', base_path('modules/Classic/resources/lang'));

Hook::add('homepageView', static fn () => view('classic::widgets/_classic')->render());
