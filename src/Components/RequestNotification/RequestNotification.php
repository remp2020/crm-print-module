<?php

namespace Crm\PrintModule\Components\RequestNotification;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;

/**
 * This widget fetches all not accepted address change requests
 * and renders bootstrap callout with list of of these requests.
 *
 * @package Crm\PrintModule\Components
 */
class RequestNotification extends BaseLazyWidget
{
    private $templateName = 'request_notification.latte';

    /** @var AddressChangeRequestsRepository */
    private $changeRequestsRepository;

    /** @var Translator */
    private $translator;

    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        AddressChangeRequestsRepository $changeRequestsRepository,
        Translator $translator
    ) {
        parent::__construct($lazyWidgetManager);
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
