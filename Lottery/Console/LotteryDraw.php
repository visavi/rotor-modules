<?php

namespace Modules\Lottery\Console;

use Illuminate\Console\Command;
use Modules\Lottery\Services\LotteryService;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class LotteryDraw extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lottery:draw';

    /**
     * The console command description.
     */
    protected $description = 'Lottery draw';

    /**
     * Разыгрывает тираж, не дожидаясь захода на страницу
     */
    public function handle(LotteryService $service): int
    {
        if (! $service->draw()) {
            $this->info('Lottery draw is not needed yet.');

            return SymfonyCommand::SUCCESS;
        }

        $this->info('Lottery successfully drawn.');

        return SymfonyCommand::SUCCESS;
    }
}
