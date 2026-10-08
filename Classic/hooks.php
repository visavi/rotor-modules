<?php

use App\Support\Registry;

Registry::homepage('classic', 'classic::classic.homepage', static fn () => view('classic::widgets/_classic'));
