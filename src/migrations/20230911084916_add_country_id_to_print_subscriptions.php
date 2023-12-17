<?php
declare(strict_types=1);

use Crm\UsersModule\Repository\CountriesRepository;
use Phinx\Migration\AbstractMigration;

final class AddCountryIdToPrintSubscriptions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET foreign_key_checks = 0');

        // first, create new column; this could take some time, but it's not blocking
        $this->execute('
            ALTER TABLE print_subscriptions
            ADD COLUMN country_id int(11) NULL AFTER phone_number
        ');

        $recordCount = $this->query('SELECT COUNT(*) AS "count" FROM print_subscriptions')->fetch()['count'];

        if ((int) $recordCount > 0) {
            $app = $GLOBALS['application'] ?? null;
            if (!$app) {
                throw new \Exception("Unable to load application from \$GLOBALS['application'], cannot load default country id.");
            }

            /** @var CountriesRepository $countriesRepository */
            $countriesRepository = $app->getContainer()->getByType(CountriesRepository::class);
            $defaultCountryId = $countriesRepository->defaultCountry()->id;

            $this->execute('UPDATE print_subscriptions SET country_id = :country_id', [
                'country_id' => $defaultCountryId
            ]);
        }

        // Add foreign key later
        $this->execute('
            ALTER TABLE print_subscriptions
            CHANGE country_id country_id int(11) NOT NULL,
            ADD FOREIGN KEY fk_country_id(country_id) REFERENCES countries(id) ON DELETE RESTRICT ON UPDATE RESTRICT
        ');

        $this->execute('SET foreign_key_checks = 1');
    }

    public function down()
    {
        $this->table('print_subscriptions')
            ->dropForeignKey('country_id')
            ->removeColumn('country_id')
            ->update();
    }
}
