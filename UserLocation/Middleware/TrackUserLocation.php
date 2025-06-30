<?php

declare(strict_types=1);

namespace Modules\UserLocation\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\UserLocation\Models\UserLocation;

class TrackUserLocation
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (
            $response instanceof Response
            && ! $response->isRedirection()
            && ! $request->is('admin*')
        ) {
            if (auth()->check()) {
                $content = $response->getContent();

                $pageTitle = __('main.undefined');
                if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                    $pageTitle = trim(Str::beforeLast($matches[1], '-'));
                }

                $query = $request->except(['_token']);
                $uri = $request->getPathInfo() . (! empty($query) ? '?' . http_build_query($query) : '');

                UserLocation::query()->updateOrCreate([
                    'user_id' => auth()->id(),
                ], [
                    'path'       => Str::substr($uri, 0, 191),
                    'title'      => Str::substr($pageTitle, 0, 191),
                    'created_at' => now(),
                ]);
            }
        }

        return $response;
    }
}
