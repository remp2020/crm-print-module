<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePrintClaimsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('print_claims')
            ->addColumn('print_subscription_id', 'integer', ['null' => false])
            ->addColumn('description', 'text')
            ->addColumn('claimant', 'string')
            ->addColumn('claimant_contact', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('closed_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex('created_at')
            ->addForeignKey('print_subscription_id', 'print_subscriptions', 'id')
            ->create();
    }
}
