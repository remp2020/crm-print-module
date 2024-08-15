<?php

namespace Crm\PrintModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\PrintModule\Forms\ChangeAddressRequestFormFactory;
use Crm\UsersModule\Repositories\AddressesRepository;

class ChangeUserAddressRequestPresenter extends FrontendPresenter
{
    private $changeAddressRequestFormFactory;

    private $addressesRepository;

    public function __construct(
        ChangeAddressRequestFormFactory $changeAddressRequestFormFactory,
        AddressesRepository $addressesRepository
    ) {
        parent::__construct();
        $this->changeAddressRequestFormFactory = $changeAddressRequestFormFactory;
        $this->addressesRepository = $addressesRepository;
    }

    public function renderChangeAddressRequest()
    {
        $this->onlyLoggedIn();
        $userRow = $this->usersRepository->find($this->getUser()->id);
        $address = $this->addressesRepository->address($userRow, 'print', true);
        $this->template->address = $address;
        $this->template->userRow = $userRow;
    }

    public function createComponentChangeAddressRequestForm()
    {
        $form = $this->changeAddressRequestFormFactory->create($this->getUser());
        $presenter = $this;
        $this->changeAddressRequestFormFactory->onSuccessRequest = function ($form, $request) use ($presenter) {
            $presenter->flashMessage($this->translator->translate('print.frontend.change_user_address.scheduled'), 'warning');
            $presenter->redirect('changeAddressRequest');
        };
        $this->changeAddressRequestFormFactory->onUserUpdate = function ($form, $user) use ($presenter) {
            $presenter->flashMessage($this->translator->translate('print.frontend.change_user_address.changed'));
            $presenter->redirect('changeAddressRequest');
        };
        return $form;
    }
}
