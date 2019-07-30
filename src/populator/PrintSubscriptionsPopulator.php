<?php

namespace Crm\PrintModule\Populator;

use Crm\ApplicationModule\Populator\AbstractPopulator;

class PrintSubscriptionsPopulator extends AbstractPopulator
{
    /**
     * @param \Symfony\Component\Console\Helper\ProgressBar $progressBar
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
                'user_id' => $user->id,
                'exported_at' => $this->faker->dateTimeBetween('-1 years'),
                'export_date' => $this->faker->dateTimeBetween('-1 years')->format('Y-m-d'),
                'first_name' => $printAddress->first_name,
                'last_name' => $printAddress->last_name,
                'address' => $printAddress->address,
                'zip' => $printAddress->zip,
                'city' => $printAddress->city,
                'email' => $user->email,
                'status' => $this->getStatus(),
                'institution_name' => $user->institution_name,
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
        return $statues[rand(0, count($statues) - 1)];
    }
}
