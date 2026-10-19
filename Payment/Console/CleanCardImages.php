<?php

namespace Modules\Payment\Console;

use Illuminate\Console\Command;
use Modules\Payment\Models\PaidAdvert;
use Modules\Payment\Services\CardImageService;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CleanCardImages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payment:clean-card-images';

    /**
     * The console command description.
     */
    protected $description = 'Delete images of unpaid advert cards';

    /**
     * Удаляет картинки, загруженные при расчёте и так и не оплаченные
     *
     * Картинка сохраняется до оплаты, а запись карточки появляется только после неё:
     * у неоплаченного заказа файл остаётся без записи. Свежие (младше суток)
     * не трогает — покупатель может быть ещё на оплате
     */
    public function handle(): int
    {
        $used = PaidAdvert::query()
            ->whereNotNull('image')
            ->pluck('image')
            ->flip();

        $deleted = 0;

        foreach (glob(public_path(CardImageService::path() . '/*')) ?: [] as $file) {
            $path = CardImageService::path() . '/' . basename($file);

            if (isset($used[$path]) || filemtime($file) > now()->subDay()->getTimestamp()) {
                continue;
            }

            unlink($file);
            $deleted++;
        }

        $this->info('Deleted card images: ' . $deleted);

        return SymfonyCommand::SUCCESS;
    }
}
