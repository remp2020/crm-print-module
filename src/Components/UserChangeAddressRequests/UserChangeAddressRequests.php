<?php

namespace Crm\PrintModule\Components\UserChangeAddressRequests;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Widget\LazyWidgetManager;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;

/**
 * This widget fetches all address change requests for specific user
 * and renders bootstrap table with list and action buttons.
 * Widget shown in admin user detail.
 *
 * @package Crm\PrintModule\Components
 */
class UserChangeAddressRequests extends BaseLazyWidget
{
    private $templateName = 'user_change_address_requests.latte';

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
        $header = $this->translator->translate('print.component.user_change_address_requests.header');
        if ($id) {
            $header .= ' <small>(' . $this->totalCount($id) . ')</small>';
        }
        return $header;
    }

    public function identifier()
    {
        return 'changeuseraddress';
    }

    public function render($id)
    {
        $this->template->userId = $id;

        $addressChangeRequests = $this->changeRequestsRepository->userRequests($id);
        $this->template->addressChangeRequests = $addressChangeRequests;
        $this->template->totalAddressChangeRequests = $this->totalCount($id);

        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }

    public function handleAcceptAddressChangeRequest($requestId)
    {
        $request = $this->changeRequestsRepository->find($requestId);
        $this->changeRequestsRepository->acceptRequest($request);
        $this->flashMessage($this->translator->translate('print.component.user_change_address_requests.messages.address_changed'));
        $this->presenter->redirect(':Users:UsersAdmin:Show', $request->user_id);
    }

    public function handleRejectAddressChangeRequest($requestId)
    {
        $request = $this->changeRequestsRepository->find($requestId);
        $this->changeRequestsRepository->rejectRequest($request);
        $this->flashMessage($this->translator->translate('print.component.user_change_address_requests.messages.change_declined'));
        $this->presenter->redirect(':Users:UsersAdmin:Show', $request->user_id);
    }

    private $totalCount = null;

    private function totalCount($id)
    {
        if ($this->totalCount == null) {
            $this->totalCount = $this->changeRequestsRepository->userRequests($id)->count('*');
        }
        return $this->totalCount;
    }
}
