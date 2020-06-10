<?php

use Modules\Lottery\Models\Lottery;
use Phinx\Migration\AbstractMigration;

class SeedToLottery extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $config = Lottery::getConfig();

        $this->execute("INSERT INTO lottery (day, amount, number) VALUES (NOW(), " . $config['jackpot'] . ", " . mt_rand($config['numberRange'][0], $config['numberRange'][1]) . ");");
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $this->execute('TRUNCATE lottery');
    }
}
