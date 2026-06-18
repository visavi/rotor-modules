<?php

declare(strict_types=1);

namespace Modules\Docs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ModuleRegistry;
use App\Services\GithubService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RotorController extends Controller
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly GithubService $githubService
    ) {
        //
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        $release = $this->githubService->getLatestRelease();

        return view('docs::rotor', compact('release'));
    }

    /**
     * Главная страница
     */
    public function releases(): View
    {
        $releases = $this->githubService->getLatestReleases();

        return view('docs::releases', compact('releases'));
    }

    /**
     * Главная страница
     */
    public function commits(Request $request): View
    {
        $commits = $this->githubService->getLatestCommits();
        $commits = paginate($commits, 10);

        return view('docs::commits', compact('commits'));
    }

    /**
     * Публичный каталог модулей
     *
     * Агрегирует модули из активных реестров. В отличие от админского
     * marketplace не фильтрует по версии сайта (это магазин — версия клиента
     * неизвестна) и отдаёт все версии модуля для выбора под свой Rotor.
     */
    public function modules(Request $request): View
    {
        $force = (bool) $request->input('refresh');
        $modules = [];

        $registries = ModuleRegistry::query()->where('active', true)->get();

        foreach ($registries as $registry) {
            $data = $registry->fetch($force);
            $registryLabel = $registry->name ?: $registry->url;

            foreach ($data['modules'] ?? [] as $module) {
                if (! isset($module['module'])) {
                    continue;
                }

                $name = $module['module'];

                if (isset($modules[$name])) {
                    $modules[$name]['conflict'][] = $registryLabel;
                    continue;
                }

                $versions = $module['versions'] ?? [];

                $modules[$name] = array_merge(
                    array_diff_key($module, ['versions' => true]),
                    $versions[0] ?? [],
                    [
                        'versions' => $versions,
                        'registry' => $registryLabel,
                        'conflict' => [],
                    ],
                );
            }
        }

        ksort($modules);

        return view('docs::modules', compact('modules'));
    }
}
