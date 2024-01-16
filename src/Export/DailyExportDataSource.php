<?php

namespace Crm\PrintModule\Export;

use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;

class DailyExportDataSource implements SourceInterface
{
    private $subscriptionRepository;

    public function __construct(SubscriptionsRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function loadData(ExportCriteria $criteria)
    {
        return $this->subscriptionRepository
            ->allSubscribers()
            ->where([
                'subscription_type:subscription_type_content_access.content_access.name' => 'print',
            ]);
    }
}
