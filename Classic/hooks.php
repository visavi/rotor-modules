<?php

use App\Support\Hook;
use App\Support\Registry;

Registry::homepage('classic', 'classic::classic.homepage', static fn () => view('classic::widgets/_classic'));
Hook::add('head', '<link rel="stylesheet" href="' . asset('assets/modules/classics/css/calendar.css') . '">');
