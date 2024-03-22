<?php

namespace Crm\PrintModule\Populators;

use Crm\ApplicationModule\Populators\AbstractPopulator;
use Symfony\Component\Console\Helper\ProgressBar;

class PrintSubscriptionsPopulator extends AbstractPopulator
{
    /**
     * @param ProgressBar $progressBar
     */
    public function seed($progressBar)
    {
        $print = $this->database->table('print_subscriptions');

        for ($i = 0; $i < $this->count; $i++) {
            $user = $this->getRecord('users');
            $printAddress = $user->related('addresses')->where('deleted_at IS NULL')->where(['type' => 'print'])->fetch();
            if (!$printAddress) {
                continue;
            }

            $data = [
                'type' => 'print',
                'user_id' => $user->id,
                'exported_at' => $this->faker->dateTimeBetween('-1 years'),
                'export_date' => $this->faker->dateTimeBetween('-1 years')->format('Y-m-d'),
                'first_name' => $printAddress->first_name,
                'last_name' => $printAddress->last_name,
                'address' => $printAddress->address,
                'zip' => $printAddress->zip,
                'city' => $printAddress->city,
                'country_id' => $printAddress->country_id,
                'email' => $user->email ?? $user->public_name,
                'status' => $this->getStatus(),
                'institution_name' => $user->institution_name,
                'meta' => '{}',
            ];
            $print->insert($data);

            $progressBar->advance();
        }
    }

    private function getStatus()
    {
        $statues = [
            'new', 'removed', 'recurrent'
        ];
        return $statues[random_int(0, count($statues) - 1)];
    }
}
