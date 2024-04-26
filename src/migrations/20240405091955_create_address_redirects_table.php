<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAddressRedirectsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('address_redirects')
            ->addColumn('original_address_id', 'integer', ['null' => false])
            ->addColumn('redirect_address_id', 'integer', ['null' => false])
            ->addColumn('from', 'datetime', ['null' => false])
            ->addColumn('to', 'datetime', ['null' => false])
            ->addColumn('note', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addForeignKey('original_address_id', 'addresses', 'id')
            ->addForeignKey('redirect_address_id', 'addresses', 'id')
            ->create();
    }
}
