<?php

namespace Crm\PrintModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\SubscriptionsModule\Subscription\ActualUserSubscription;
use Crm\UsersModule\Repository\AddressesRepository;

/**
 * This widget fetches users actual subscription and renders link and info
 * about missing pritn address in case user have print subscription and doesn't have print address filled.
 *
 * @package Crm\PrintModule\Components
 */
class EnterAddressWidget extends BaseWidget
{
    protected $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'enter_address_widget.latte';

    private $actualUserSubscription;

    private $addressesRepository;

    public function __construct(
        WidgetManager $widgetManager,
        ActualUserSubscription $actualUserSubscription,
        AddressesRepository $addressesRepository
    ) {
        parent::__construct($widgetManager);
        $this->actualUserSubscription = $actualUserSubscription;
        $this->addressesRepository = $addressesRepository;
    }

    public function identifier()
    {
        return 'enteraddresswidget';
    }

    public function render($id)
    {
        $actualSubscription = $this->actualUserSubscription->getSubscription();
        if (!$actualSubscription) {
            return null;
        }

        if (!$actualSubscription->subscription_type->print && !$actualSubscription->subscription_type->print_friday) {
            return null;
        }

        $hasAddress = $this->addressesRepository->address($actualSubscription->user, 'print');
        if ($hasAddress) {
            return null;
        }

        $this->template->setFile($this->templatePath);
        $this->template->render();
    }
}
