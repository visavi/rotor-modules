<?php

use App\Classes\Hook;

Hook::add('homepageView', static fn () => view('classic::widgets/_classic')->render());
