<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddClaimTypeColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('print_claims')
            ->addColumn('claim_type', 'string', ['after' => 'claimant_contact'])
            ->update();
    }
}
