<?php

namespace Crm\PrintModule\Populator;

use Crm\ApplicationModule\Populator\AbstractPopulator;

class AddressesPopulator extends AbstractPopulator
{
    /**
     * @param \Symfony\Component\Console\Helper\ProgressBar $progressBar
     */
    public function seed($progressBar)
    {
        $table = $this->database->table('addresses');
        $country = $this->database->table('countries')->where(['name' => 'Slovensko'])->fetch();

        for ($i = 0; $i < $this->count; $i++) {
            $user = $this->getRecord('users');
            $addressType = $this->getRecord('address_types');

            $data = [
                'user_id' => $user->id,
                'country_id' => $country->id,
                'type' => $addressType->type,
                'created_at' => $this->faker->dateTimeBetween('-2 years'),
                'updated_at' => $this->faker->dateTimeBetween('-2 years'),
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'address' => $this->faker->streetAddress,
                'number' => $this->faker->buildingNumber,
                'phone_number' => $this->faker->phoneNumber,
                'company_name' => $this->faker->company,
                'city' => $this->faker->city,
                'zip' => $this->faker->postcode,
            ];
            $table->insert($data);
            $progressBar->advance();
        }
    }
}
