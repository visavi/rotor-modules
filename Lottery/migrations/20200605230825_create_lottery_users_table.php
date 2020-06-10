<?php

use Phinx\Migration\AbstractMigration;

class CreateLotteryUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('lottery_users', ['engine' => config('DB_ENGINE'), 'collation' => config('DB_COLLATION')]);
        $table
            ->addColumn('lottery_id', 'integer')
            ->addColumn('user_id', 'integer')
            ->addColumn('number', 'smallinteger', ['limit' => 3])
            ->addColumn('created_at', 'integer')
            ->create();
    }
}
