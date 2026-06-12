<?php

use App\Classes\Hook;

// Курсы валют в нижней части сайдбара (темы default, nordic, newspaper)
Hook::add('sidebarFooterEnd', static fn () => '<li class="mt-3">' . getCurrencyRates() . '</li>');
