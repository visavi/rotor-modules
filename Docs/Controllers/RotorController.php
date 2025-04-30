<?php

declare(strict_types=1);

namespace Modules\Docs\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Docs\Services\GithubService;

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

        return view('Docs::rotor', compact('release'));
    }

    /**
     * Главная страница
     */
    public function releases(): View
    {
        $releases = $this->githubService->getLatestReleases();

        return view('Docs::releases', compact('releases'));
    }

    /**
     * Главная страница
     */
    public function commits(Request $request): View
    {
        $commits = $this->githubService->getLatestCommits();
        $commits = paginate($commits, 10);

        return view('Docs::commits', compact('commits'));
    }
}
