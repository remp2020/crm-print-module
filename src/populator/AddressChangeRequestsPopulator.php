<?php

namespace Crm\PrintModule\Populator;

use Crm\ApplicationModule\Populator\AbstractPopulator;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;

class AddressChangeRequestsPopulator extends AbstractPopulator
{
    /**
     * @param \Symfony\Component\Console\Helper\ProgressBar $progressBar
     */
    public function seed($progressBar)
    {
        $table = $this->database->table('address_change_requests');

        for ($i = 0; $i < $this->count; $i++) {
            $user = $this->getRecord('users');

            $addresses = $user->related('addresses')->where('deleted_at IS NULL')->fetchAll();
            if (empty($addresses)) {
                continue;
            }

            $data = [
                'user_id' => $user->id,
                'address_id' => $this->getAddress($addresses),
                'status' => $this->getStatus(),
                'created_at' => $this->faker->dateTimeBetween('-2 years'),
                'updated_at' => $this->faker->dateTimeBetween('-2 years'),
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'address' => $this->faker->streetAddress,
                'phone_number' => $this->faker->phoneNumber,
                'city' => $this->faker->city,
                'zip' => $this->faker->postcode,
            ];
            $table->insert($data);
            $progressBar->advance();
        }
    }

    private function getStatus()
    {
        $items = [
            AddressChangeRequestsRepository::STATUS_NEW,
            AddressChangeRequestsRepository::STATUS_ACCEPTED,
            AddressChangeRequestsRepository::STATUS_REJECTED,
        ];
        return $items[rand(0, count($items) - 1)];
    }

    private function getAddress($addresses)
    {
        $ids = array_keys($addresses);
        $randomId = $ids[rand(0, count($ids)-1)];
        return $addresses[$randomId];
    }
}
