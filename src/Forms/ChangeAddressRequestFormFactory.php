<?php

namespace Crm\PrintModule\Forms;

use Crm\ApplicationModule\Forms\Controls\CountriesSelectItemsBuilder;
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

    private User $user;
    private string $addressType;

    private $request;

    public function __construct(
        private readonly UsersRepository $usersRepository,
        private readonly AddressChangeRequestsRepository $addressChangeRequestsRepository,
        private readonly AddressesRepository $addressesRepository,
        private readonly CountriesRepository $countriesRepository,
        private readonly Translator $translator,
        private readonly DataProviderManager $dataProviderManager,
        private readonly CountriesSelectItemsBuilder $countriesSelectItemsBuilder,
    ) {
    }

    public function create(User $user, string $addressType = 'print'): Form
    {
        $form = new Form;

        $this->user = $user;
        $this->addressType = $addressType;

        $row = $this->loadUserRow();
        $addressRow = $this->addressesRepository->address($row, $addressType, true);
        if (!$addressRow) {
            $addressRow = $this->addressesRepository->address($row, $addressType);
        }

        $defaults = [];
        if ($addressRow) {
            $defaults = [
                'first_name' => $addressRow->first_name,
                'last_name' => $addressRow->last_name,
                'phone_number' => $addressRow->phone_number,
                'address' => $addressRow->address,
                'number' => $addressRow->number,
                'zip' => $addressRow->zip,
                'city' => $addressRow->city,
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
        $form->addSelect('country_id', $this->translator->translate('print.frontend.change_address_request_form.country.label'), $this->countriesSelectItemsBuilder->getDefaultCountryPair())
            ->setOption('id', 'country_id')
            ->setRequired($this->translator->translate('print.frontend.change_address_request_form.country.required'))
            ->setDisabled();

        $userRow = $this->usersRepository->find($user->id);
        if ($addressRow) {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_update'));
        } else {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_create'));
        }

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];

        /** @var AddressFormDataProviderInterface[] $providers */
        $providers = $this->dataProviderManager->getProviders('print.dataprovider.change_address_form', AddressFormDataProviderInterface::class);
        foreach ($providers as $sorting => $provider) {
            $form = $provider->provide(['form' => $form, 'addressType' => $this->addressType, 'user' => $userRow]);
        }

        $form->onSuccess[] = [$this, 'formSucceededAfterProviders'];

        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $userRow = $this->loadUserRow();

        $printAddress = $this->addressesRepository->address($userRow, $this->addressType, true);

        $this->request = $request = $this->addressChangeRequestsRepository->add(
            user: $userRow,
            parentAddress: $printAddress,
            firstName: $values['first_name'],
            lastName: $values['last_name'],
            companyName: null,
            address: $values['address'],
            number: $values['number'],
            city: $values['city'],
            zip: $values['zip'],
            countryId: $this->countriesRepository->defaultCountry()->id,
            companyId: null,
            companyTaxId: null,
            companyVatId: null,
            phoneNumber: $values['phone_number'],
            type: $this->addressType
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
        $printAddress = $this->addressesRepository->address($userRow, $this->addressType);

        if (!$printAddress) {
            $this->onUserUpdate->__invoke($form, $this->request);
            return;
        }

        $this->onSuccessRequest->__invoke($form, $this->request);
    }
}
