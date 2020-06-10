<?php

use Phinx\Migration\AbstractMigration;

class CreateLotteryTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery', ['engine' => config('DB_ENGINE'), 'collation' => config('DB_COLLATION')]);
        $table
            ->addColumn('day', 'date')
            ->addColumn('amount', 'integer')
            ->addColumn('number', 'smallinteger', ['limit' => 3])
            ->addIndex('day')
            ->create();
    }
}
