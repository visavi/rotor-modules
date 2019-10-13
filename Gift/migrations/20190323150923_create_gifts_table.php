<?php

use Phinx\Migration\AbstractMigration;

class CreateGiftsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('gifts', ['engine' => config('DB_ENGINE'), 'collation' => config('DB_COLLATION')]);
        $table
            ->addColumn('name', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('path', 'string', ['limit' => 100])
            ->addColumn('price', 'integer', ['default' => 0])
            ->addColumn('created_at', 'integer')
            ->create();
    }
}
