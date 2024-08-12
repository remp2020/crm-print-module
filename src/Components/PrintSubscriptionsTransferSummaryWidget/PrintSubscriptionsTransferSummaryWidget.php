<?php

namespace Crm\PrintModule\Components\PrintSubscriptionsTransferSummaryWidget;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Exception;
use Nette\Database\Table\ActiveRow;

class PrintSubscriptionsTransferSummaryWidget extends BaseLazyWidget
{
    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        private readonly PrintSubscriptionsRepository $printSubscriptionsRepository,
    ) {
        parent::__construct($lazyWidgetManager);
    }

    public function identifier(): string
    {
        return 'printsubscriptionstransfersummarywidget';
    }

    public function render(array $params): void
    {
        if (!isset($params['subscription'])) {
            throw new Exception("Missing required param 'subscription'.");
        }

        $subscription = $params['subscription'];

        $this->template->printSubscription = $this->getLatestPrintSubscription($subscription);

        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'print_subscriptions_transfer_summary_widget.latte');
        $this->template->render();
    }

    private function getLatestPrintSubscription(mixed $subscription): ?ActiveRow
    {
        return $this->printSubscriptionsRepository->getTable()
            ->where('subscription_id', $subscription->id)
            ->order('export_date DESC')
            ->limit(1)
            ->fetch();
    }
}
