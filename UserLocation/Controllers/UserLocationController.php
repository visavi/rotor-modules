<?php

declare(strict_types=1);

namespace Modules\UserLocation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\UserLocation\Models\UserLocation;

class UserLocationController extends Controller
{
    /**
     * Main page
     */
    public function index(): View
    {
        $locations = UserLocation::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('user_location::index', compact('locations'));
    }
}
