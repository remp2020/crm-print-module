<?php

use Phinx\Migration\AbstractMigration;

class AnonymizeDeletedAddresses extends AbstractMigration
{
    public function up()
    {
        $sql = <<<SQL
UPDATE print_subscriptions
INNER JOIN (
        SELECT t2.address_id, t2.type from
            -- Find latest record for each user_id-type pair, so we can investigate whether it's "removed"
            (SELECT ps.type, ps.user_id, MAX(ps.export_date) AS export_date
             FROM addresses a
                      JOIN users u ON u.id = a.user_id
                 AND a.deleted_at IS NOT NULL
                 AND u.deleted_at IS NOT NULL
                      JOIN print_subscriptions ps ON ps.address_id = a.id
             GROUP BY ps.type, ps.user_id) t1
                JOIN print_subscriptions t2 ON t1.user_id = t2.user_id AND t1.export_date = t2.export_date AND t1.type = t2.type

        GROUP BY t2.address_id, t2.type
        HAVING SUM(CASE WHEN t2.status != 'removed' THEN 1 else 0 END) = 0 -- Select only records, which were already flagged as removed in one of the exports
    ) to_remove ON to_remove.address_id = print_subscriptions.address_id AND to_remove.type = print_subscriptions.type
SET first_name = 'GDPR removal', last_name = 'GDPR removal', address = 'GDPR removal', number = 'GDPR removal', zip = 'GDPR removal', city = 'GDPR removal', phone_number = 'GDPR removal', institution_name = 'GDPR removal', email = 'GDPR removal'

SQL;
        $this->execute($sql);
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available, up migration was destructive.');
    }
}
