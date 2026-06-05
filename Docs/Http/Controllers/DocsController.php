<?php

declare(strict_types=1);

namespace Modules\Docs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Docs\Services\DocsService;

class DocsController extends Controller
{
    public function __construct(private readonly DocsService $docs)
    {
    }

    public function show(string $page = 'rotor-installation'): View
    {
        $rotorPath = base_path("modules/Docs/resources/docs/{$page}.md");
        $laravelPath = base_path("modules/Docs/resources/laravel-docs/{$page}.md");

        if (file_exists($rotorPath)) {
            $raw = file_get_contents($rotorPath);
            $content = Str::of($raw)->markdown(['html_input' => 'strip']);
            $title = $this->docs->extractTitle($raw) ?? $page;
            $section = 'rotor';
            $menu = $this->docs->buildMenu($page, null);

            return view('docs::show', compact('content', 'title', 'menu', 'page', 'section'));
        }

        if (file_exists($laravelPath)) {
            $synced = true;
            $raw = file_get_contents($laravelPath);
            $content = Str::of($raw)
                ->replace('{{version}}', DocsService::LARAVEL_VERSION)
                ->after('---')
                ->after('---')
                ->markdown(['html_input' => 'strip']);
            $title = $this->docs->extractTitle($raw) ?? $page;
            $section = 'laravel';
            $menu = $this->docs->buildMenu(null, $page);

            return view('docs::show', compact('content', 'title', 'menu', 'page', 'section', 'synced'));
        }

        if (! file_exists(base_path('modules/Docs/resources/laravel-docs/installation.md'))) {
            $synced = false;
            $content = null;
            $title = 'Документация не загружена';
            $section = 'laravel';
            $menu = $this->docs->buildMenu(null, null);

            return view('docs::show', compact('content', 'title', 'menu', 'page', 'section', 'synced'));
        }

        abort(404);
    }

    public function search(Request $request): View
    {
        $query = trim($request->get('q', ''));
        $results = mb_strlen($query) >= 3 ? $this->docs->search($query) : [];
        $menu = $this->docs->buildMenu(null, null);

        return view('docs::search', compact('query', 'results', 'menu'));
    }
}
