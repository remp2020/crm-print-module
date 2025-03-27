<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenameStreetPartOfPrintSubscriptionsToStreet extends AbstractMigration
{
    public function change(): void
    {
        $this->table('print_subscriptions')
            ->renameColumn('address', 'street')
            ->update();
    }
}
