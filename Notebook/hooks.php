<?php

use App\Classes\Hook;

Hook::add('menuActivity', static fn () => '<i class="far fa-circle text-muted"></i> <a href="' . route('notebooks.index') . '">' . __('notebook::notebooks.notebook') . '</a><br>');
