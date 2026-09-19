<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SocialAuth\Models\Social;

class SocialController extends Controller
{
    /**
     * Список привязок соцсетей
     */
    public function index(Request $request): View
    {
        $provider = (string) $request->input('provider');

        if (! isset(Social::PROVIDERS[$provider])) {
            $provider = '';
        }

        $socials = Social::query()
            ->when($provider, static fn ($query) => $query->where('provider', $provider))
            ->orderByDesc('last_login_at')
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(setting('userlist'))
            ->appends(['provider' => $provider ?: null]);

        // Счетчики привязок и активных за последний месяц по каждому провайдеру
        $stats = Social::query()
            ->selectRaw('provider, count(*) as total, sum(case when last_login_at >= ? then 1 else 0 end) as active', [now()->subMonth()])
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        $providers = Social::PROVIDERS;

        return view('social_auth::admin/index', compact('socials', 'stats', 'provider', 'providers'));
    }
}
