<?php

namespace Crm\PrintModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;
use Kdyby\Translation\Translator;

/**
 * This widget fetches all not accepted address change requests
 * and renders bootstrap callout with list of of these requests.
 *
 * @package Crm\PrintModule\Components
 */
class RequestNotification extends BaseWidget
{
    private $templateName = 'request_notification.latte';

    /** @var AddressChangeRequestsRepository */
    private $changeRequestsRepository;

    /** @var WidgetManager */
    protected $widgetManager;

    /** @var Translator */
    private $translator;

    public function __construct(
        WidgetManager $widgetManager,
        AddressChangeRequestsRepository $changeRequestsRepository,
        Translator $translator
    ) {
        parent::__construct($widgetManager);
        $this->changeRequestsRepository = $changeRequestsRepository;
        $this->translator = $translator;
    }

    public function header($id = '')
    {
        return $this->translator->translate('print.component.requests_notification.header');
    }

    public function identifier()
    {
        return 'changeuseraddressnotification';
    }

    public function render($id = '')
    {
        $addressChangeRequests = $this->changeRequestsRepository->allNewRequests();
        $this->template->addressChangeRequests = $addressChangeRequests;

        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }
}
