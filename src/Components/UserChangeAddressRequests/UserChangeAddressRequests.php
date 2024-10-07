<?php

namespace Crm\PrintModule\Components\UserChangeAddressRequests;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;
use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Database\Table\Selection;

/**
 * This widget fetches all address change requests for specific user
 * and renders bootstrap table with list and action buttons.
 * Widget shown in admin user detail.
 *
 * @package Crm\PrintModule\Components
 */
class UserChangeAddressRequests extends BaseLazyWidget
{
    private string $templateName = 'user_change_address_requests.latte';
    private ?int $totalCount = null;


    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        private readonly AddressChangeRequestsRepository $changeRequestsRepository,
        private readonly AddressesRepository $addressesRepository,
        private readonly Translator $translator
    ) {
        parent::__construct($lazyWidgetManager);
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

        $this->template->addressChanges = $this->getUserAddressChanges($id);
        $this->template->totalAddressChanges = $this->totalCount($id);

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

    private function totalCount($id)
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->userRequests($id)->count('*')
                + $this->userDeletedAddresses($id)->count('*');
        }
        return $this->totalCount;
    }

    private function getUserAddressChanges($userId): array
    {
        $requests = $this->userRequests($userId)->fetchAll();
        $addresses = $this->userDeletedAddresses($userId)->fetchAll();

        $mergedArray = [... $requests, ...$addresses];
        usort($mergedArray, static function ($a, $b) {
            // only address has deleted_at, both address and change request have created_at
            return ($b['deleted_at'] ?? $b['created_at']) <=> ($a['deleted_at'] ?? $a['created_at']);
        });

        return $mergedArray;
    }

    private function userRequests($userId): Selection
    {
        return $this->changeRequestsRepository->getTable()
            ->where('user_id', $userId)
            ->order('created_at DESC');
    }

    private function userDeletedAddresses($userId): Selection
    {
        return $this->addressesRepository->getTable()
            ->where([
                'user_id' => $userId,
            ])
            ->where('deleted_at IS NOT NULL')
            ->order('deleted_at DESC');
    }
}
