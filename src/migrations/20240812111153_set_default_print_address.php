<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetDefaultPrintAddress extends AbstractMigration
{

    public function up(): void
    {
        $sql = <<<SQL
            WITH ranked_addresses AS (
                SELECT
                    id,
                    ROW_NUMBER() OVER (
                        PARTITION BY user_id
                        ORDER BY updated_at DESC, id DESC
                    ) AS rn
                FROM addresses
                WHERE type = 'print'
            )
            UPDATE addresses
            SET is_default = true
            WHERE id IN (
                SELECT id
                FROM ranked_addresses
                WHERE rn = 1
            );
        SQL;

        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = <<<SQL
            UPDATE addresses
            SET `is_default` = false
            WHERE type = 'print'
        SQL;

        $this->execute($sql);
    }
}
