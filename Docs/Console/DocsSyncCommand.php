<?php

declare(strict_types=1);

namespace Modules\Docs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DocsSyncCommand extends Command
{
    protected $signature = 'docs:sync {--branch=12.x : Ветка репозитория laravelsu/docs}';

    protected $description = 'Синхронизация Laravel документации с репозитория laravelsu/docs';

    public function handle(): int
    {
        $version = $this->option('branch');
        $dir = base_path('modules/Docs/resources/laravel-docs');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info("Загрузка списка файлов для версии {$version}...");

        $response = Http::withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(15)
            ->get("https://api.github.com/repos/laravelsu/docs/contents/?ref={$version}");

        if ($response->failed()) {
            $this->error('Ошибка GitHub API: ' . $response->status());

            return self::FAILURE;
        }

        $files = collect($response->json())
            ->filter(fn ($f) => isset($f['type']) && $f['type'] === 'file' && str_ends_with($f['name'], '.md'));

        $remoteNames = $files->pluck('name')->all();

        foreach (glob("{$dir}/*.md") as $existing) {
            if (! in_array(basename($existing), $remoteNames, true)) {
                unlink($existing);
                $this->line('Удалён: ' . basename($existing));
            }
        }

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $file) {
            $content = Http::timeout(10)->get($file['download_url'])->body();
            file_put_contents("{$dir}/{$file['name']}", $content);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Загружено {$files->count()} файлов в {$dir}");

        return self::SUCCESS;
    }
}
