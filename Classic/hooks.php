<?php

use App\Support\Hook;

Hook::add('homepageView', view('classic::widgets/_classic'));
Hook::add('head', '<link rel="stylesheet" href="' . asset('assets/modules/classics/css/calendar.css') . '">');
