<?php

use Phinx\Migration\AbstractMigration;

class SeedToGifts extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $gifts = array_map('basename', glob(APP . '/Modules/Gift/assets/gifts/*.{gif,png,jpg,jpeg}', GLOB_BRACE));

        foreach ($gifts as $gift) {

            $this->execute("INSERT INTO gifts (path, price, created_at) VALUES ('/assets/modules/gifts/" . $gift . "', " . (mt_rand(1, 10) * 100) . ", " . SITETIME . ");");
        }
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $this->execute('TRUNCATE gifts');
    }
}
