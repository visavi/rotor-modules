<?php

namespace Modules\Docs\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GithubService
{
    protected string $baseUrl = 'https://api.github.com/repos/visavi/rotor/';
    protected int $defaultCacheTtl = 3600;

    /**
     * Получает последние коммиты
     */
    public function getLatestCommits(): array
    {
        return Cache::remember('commits', $this->defaultCacheTtl, function () {
            return $this->fetchGitHubData(
                endpoint: 'commits',
                params: ['per_page' => 100]
            );
        });
    }

    /**
     * Get last release
     */
    public function getLatestRelease(): array
    {
        return $this->getLatestReleases()[0] ?? [];
    }

    /**
     * Get last version
     */
    public function getLatestVersion(): string
    {
        $release = $this->getLatestRelease();

        return $release['tag_name'] ?? '';
    }

    /**
     * Получает последние релизы
     */
    public function getLatestReleases(): array
    {
        return Cache::remember('releases', $this->defaultCacheTtl, function () {
            return $this->fetchGitHubData(
                endpoint: 'releases',
                params: ['per_page' => 10]
            );
        });
    }

    /**
     * Обращается к GitHub API
     */
    protected function fetchGitHubData(string $endpoint, array $params = []): array
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(3)
                ->retry(3, 100)
                ->get($this->baseUrl . $endpoint, $params);

            if ($response->failed()) {
                throw new RuntimeException(
                    'GitHub API error: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json() ?? [];
        } catch (Throwable) {
            return [];
        }
    }
}
