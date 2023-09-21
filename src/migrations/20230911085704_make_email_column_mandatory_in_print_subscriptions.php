<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeEmailColumnMandatoryInPrintSubscriptions extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
            UPDATE print_subscriptions
                JOIN users u on u.id = print_subscriptions.user_id
            SET print_subscriptions.email=u.email
            WHERE print_subscriptions.email IS NULL
        SQL;
        $this->execute($sql);

        $this->table('print_subscriptions')
            ->changeColumn('email', 'string', ['null' => false])
            ->update();
    }

    public function down()
    {
        $this->output->writeln('Down migration for data is not available. Changing column `email` back to nullable.');

        $this->table('print_subscriptions')
            ->changeColumn('email', 'string', ['null' => true])
            ->update();
    }
}
