<?php

namespace Crm\PrintModule\Components\PrintAddressTransferSummaryWidget;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\PrintModule\DataProviders\SubscriptionTransfer\AddressToTransferRetriever;
use Exception;

class PrintAddressTransferSummaryWidget extends BaseLazyWidget
{
    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        private readonly AddressToTransferRetriever $addressToTransferRetriever,
    ) {
        parent::__construct($lazyWidgetManager);
    }

    public function identifier(): string
    {
        return 'printaddresstransfersummarywidget';
    }

    public function render(array $params): void
    {
        if (!isset($params['subscription'])) {
            throw new Exception("Missing required param 'subscription'.");
        }

        $subscription = $params['subscription'];

        $address = $this->addressToTransferRetriever->retrieve($subscription);
        if ($address === null) {
            return;
        }

        $this->template->address = $address;
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . 'print_address_transfer_summary_widget.latte');
        $this->template->render();
    }
}
