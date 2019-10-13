<?php

use Phinx\Migration\AbstractMigration;

class CreateGiftsUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('gifts_users', ['engine' => config('DB_ENGINE'), 'collation' => config('DB_COLLATION')]);
        $table
            ->addColumn('gift_id', 'integer')
            ->addColumn('user_id', 'integer')
            ->addColumn('send_user_id', 'integer', ['null' => true])
            ->addColumn('text', 'text', ['null' => true])
            ->addColumn('created_at', 'integer')
            ->addColumn('deleted_at', 'integer')
            ->create();
    }
}
