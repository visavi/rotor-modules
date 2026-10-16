<?php

namespace Modules\MailNotify\Console;

use App\Models\User;
use App\Services\MailService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class AddBirthdays extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'add:birthdays';

    /**
     * The console command description.
     */
    protected $description = 'Add birthdays';

    /**
     * Добавляет именинников в рассылку
     */
    public function handle(): int
    {
        $deliveryUsers = User::query()
            ->where('point', '>', 0)
            ->whereIn('level', User::USER_GROUPS)
            ->whereRaw('substr(birthday, 1, 5) = ?', now()->format('d.m'))
            ->whereNotNull('email')
            ->whereNotNull('subscribe')
            ->get();

        $mail = app(MailService::class);
        $packet = max(1, (int) setting('sendmailpacket'));

        $subject = __('mail_notify::mail_notify.birthday_subject', ['site' => setting('title')]);

        foreach ($deliveryUsers->values() as $index => $user) {
            // Письма растаскиваются по минутам: суточный лимит релея
            // не должен выгорать одним залпом
            $mail->queue('mail_notify::mailer.birthday', [
                'to'          => $user->email,
                'subject'     => $subject,
                'username'    => $user->getName(),
                'unsubscribe' => $user->subscribe,
            ], intdiv((int) $index, $packet));
        }

        $this->info('Birthdays successfully added.');

        return SymfonyCommand::SUCCESS;
    }
}
