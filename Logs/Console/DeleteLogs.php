<?php

declare(strict_types=1);

namespace Modules\Logs\Console;

use Illuminate\Console\Command;
use Modules\Logs\Models\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class DeleteLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'delete:logs';

    /**
     * The console command description.
     */
    protected $description = 'Delete old visit logs';

    /**
     * Удаляет старые записи логов
     */
    public function handle(): int
    {
        Log::query()
            ->where('created_at', '<', now()->subMonth())
            ->delete();

        $this->info('Logs successfully deleted.');

        return SymfonyCommand::SUCCESS;
    }
}
