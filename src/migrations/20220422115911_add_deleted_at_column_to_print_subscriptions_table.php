<?php

use Phinx\Migration\AbstractMigration;

class AddDeletedAtColumnToPrintSubscriptionsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('print_subscriptions')
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->update();
    }
}
