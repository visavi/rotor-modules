<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('statsChecker')) {
    function statsChecker(): string
    {
        if (Storage::disk('local')->exists('checker.php')) {
            return dateFixed(Storage::disk('local')->lastModified('checker.php'));
        }

        return '0';
    }
}
