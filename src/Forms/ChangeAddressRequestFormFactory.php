<?php

namespace Crm\PrintModule\Forms;

use Crm\ApplicationModule\Models\DataProvider\DataProviderManager;
use Crm\UsersModule\DataProviders\AddressFormDataProviderInterface;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;
use Crm\UsersModule\Repositories\AddressesRepository;
use Crm\UsersModule\Repositories\CountriesRepository;
use Crm\UsersModule\Repositories\UsersRepository;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use Nette\Security\User;
use Tomaj\Form\Renderer\BootstrapRenderer;

class ChangeAddressRequestFormFactory
{
    /* callback function */
    public $onSuccessRequest;

    /* callback function */
    public $onUserUpdate;

    private $user;

    private $request;

    public function __construct(
        private UsersRepository $usersRepository,
        private AddressChangeRequestsRepository $addressChangeRequestsRepository,
        private AddressesRepository $addressesRepository,
        private CountriesRepository $countriesRepository,
        private Translator $translator,
        private DataProviderManager $dataProviderManager,
    ) {
    }

    public function create(User $user): Form
    {
        $form = new Form;
        $this->user = $user;

        $row = $this->loadUserRow();

        $printAddress = $this->addressesRepository->address($row, 'print');

        $defaults = [];
        if ($printAddress) {
            $defaults = [
                'first_name' => $printAddress->first_name,
                'last_name' => $printAddress->last_name,
                'phone_number' => $printAddress->phone_number,
                'address' => $printAddress->address,
                'number' => $printAddress->number,
                'zip' => $printAddress->zip,
                'city' => $printAddress->city,
            ];
        }

        $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();

        $form->addText('first_name', $this->translator->translate('print.frontend.change_address_request_form.first_name.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.first_name.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.first_name.required'));
        $form->addText('last_name', $this->translator->translate('print.frontend.change_address_request_form.last_name.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.last_name.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.last_name.required'));
        $form->addText('phone_number', $this->translator->translate('print.frontend.change_address_request_form.phone_number.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.phone_number.placeholder'));
        $form->addText('address', $this->translator->translate('print.frontend.change_address_request_form.address.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.address.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.address.required'));
        $form->addText('number', $this->translator->translate('print.frontend.change_address_request_form.number.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.number.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.number.required'));
        $form->addText('zip', $this->translator->translate('print.frontend.change_address_request_form.zip.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.zip.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.zip.required'));
        $form->addText('city', $this->translator->translate('print.frontend.change_address_request_form.city.label'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('print.frontend.change_address_request_form.city.placeholder'))
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.city.required'));
        $form->addSelect('country_id', $this->translator->translate('print.frontend.change_address_request_form.country.label'), $this->countriesRepository->getDefaultCountryPair())
            ->setOption('id', 'country_id')
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.country.required'))
            ->setDisabled();

        $userRow = $this->usersRepository->find($user->id);
        if ($this->addressesRepository->address($userRow, 'print')) {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_update'));
        } else {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_create'));
        }

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];

        /** @var AddressFormDataProviderInterface[] $providers */
        $providers = $this->dataProviderManager->getProviders('print.dataprovider.change_address_form', AddressFormDataProviderInterface::class);
        foreach ($providers as $sorting => $provider) {
            $form = $provider->provide(['form' => $form, 'addressType' => 'print', 'user' => $userRow]);
        }

        $form->onSuccess[] = [$this, 'formSucceededAfterProviders'];

        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $userRow = $this->loadUserRow();

        $printAddress = $this->addressesRepository->address($userRow, 'print');

        $this->request = $request = $this->addressChangeRequestsRepository->add(
            $userRow,
            $printAddress,
            $values['first_name'],
            $values['last_name'],
            null,
            $values['address'],
            $values['number'],
            $values['city'],
            $values['zip'],
            $this->countriesRepository->defaultCountry()->id,
            null,
            null,
            null,
            $values['phone_number'],
            'print'
        );

        if (!$printAddress) {
            // accept the initial request directly
            $this->addressChangeRequestsRepository->acceptRequest($request);
        }
    }

    private function loadUserRow()
    {
        $row = $this->usersRepository->find($this->user->id);
        if (!$row) {
            throw new BadRequestException();
        }
        return $row;
    }

    public function formSucceededAfterProviders(Form $form, $values): void
    {
        $userRow = $this->loadUserRow();
        $printAddress = $this->addressesRepository->address($userRow, 'print');

        if (!$printAddress) {
            $this->onUserUpdate->__invoke($form, $this->request);
            return;
        }

        $this->onSuccessRequest->__invoke($form, $this->request);
    }
}
