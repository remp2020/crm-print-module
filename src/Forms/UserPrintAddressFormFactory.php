<?php

namespace Crm\PrintModule\Forms;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\DataProvider\DataProviderManager;
use Crm\UsersModule\DataProvider\AddressFormDataProviderInterface;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;
use Crm\UsersModule\Repository\AddressesRepository;
use Crm\UsersModule\Repository\CountriesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Tomaj\Form\Renderer\BootstrapRenderer;

class UserPrintAddressFormFactory
{
    private $usersRepository;
    private $addressesRepository;
    private $countriesRepository;
    private $addressChangeRequestsRepository;

    private $dataProviderManager;

    /* callback function */
    public $onSave;

    /** @var ActiveRow */
    private $payment;

    private $translator;

    private $addressType;

    public function __construct(
        Translator $translator,
        UsersRepository $usersRepository,
        AddressesRepository $addressesRepository,
        AddressChangeRequestsRepository $addressChangeRequestsRepository,
        CountriesRepository $countriesRepository,
        DataProviderManager $dataProviderManager
    ) {
        $this->translator = $translator;
        $this->usersRepository = $usersRepository;
        $this->addressesRepository = $addressesRepository;
        $this->addressChangeRequestsRepository = $addressChangeRequestsRepository;
        $this->countriesRepository = $countriesRepository;
        $this->dataProviderManager = $dataProviderManager;
    }

    public function create(ActiveRow $payment, string $addressType = 'print'): Form
    {
        $this->addressType = $addressType;

        $form = new Form;

        $this->payment = $payment;
        $user = $this->payment->user;

        $printAddress = $this->addressesRepository->address($user, $this->addressType);

        $form->addProtection();
        $form->setTranslator($this->translator);
        $form->setRenderer(new BootstrapRenderer());
        $form->getElementPrototype()->addClass('ajax');

        $form->addText('first_name', 'print.form.print_address.label.name')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.name')
            ->setRequired('print.form.print_address.required.name');
        $form->addText('last_name', 'print.form.print_address.label.last_name')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.last_name')
            ->setRequired('print.form.print_address.required.last_name');
        $form->addText('phone_number', 'print.form.print_address.label.phone_number')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.phone_number');
        $form->addText('address', 'print.form.print_address.label.address')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.address')
            ->setRequired('print.form.print_address.required.address');
        $form->addText('number', 'print.form.print_address.label.number')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.number')
            ->setRequired('print.form.print_address.required.number');
        $form->addText('zip', 'print.form.print_address.label.zip')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.zip')
            ->setRequired('print.form.print_address.required.zip');
        $form->addText('city', 'print.form.print_address.label.city')
            ->setHtmlAttribute('placeholder', 'print.form.print_address.placeholder.city')
            ->setRequired('print.form.print_address.required.city');
        $form->addSelect('country_id', 'print.form.print_address.label.country_id', $this->countriesRepository->getDefaultCountryPair())
            ->setRequired('print.form.print_address.required.country_id');

        $form->addHidden('VS', $payment->variable_symbol);

        $form->addHidden('done', $printAddress ? 1 : 0)->setHtmlId('printAddressDone');

        $form->addSubmit('send', 'print.form.print_address.label.save')
            ->getControlPrototype()
            ->setName('button')
            ->setAttribute('class', 'btn btn-success')
            ->setAttribute('style', 'float: right');

        $defaults = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'invoice' => $user->invoice,
        ];

        if ($printAddress) {
            $defaults = array_merge($defaults, [
                'first_name' => $printAddress->first_name,
                'last_name' => $printAddress->last_name,
                'phone_number' => $printAddress->phone_number,
                'address' => $printAddress->address,
                'number' => $printAddress->number,
                'zip' => $printAddress->zip,
                'city' => $printAddress->city,
            ]);
        }

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];

        /** @var AddressFormDataProviderInterface[] $providers */
        $providers = $this->dataProviderManager->getProviders('sales_funnel.dataprovider.address_form', AddressFormDataProviderInterface::class);
        foreach ($providers as $sorting => $provider) {
            $form = $provider->provide(['form' => $form, 'payment' => $payment, 'address' => $printAddress, 'self' => $this, 'addressType' => 'print']);
        }

        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $user = $this->payment->user;

        if (isset($values->first_name)) {
            $printAddress = $this->addressesRepository->address($user, $this->addressType);

            $changeRequest = $this->addressChangeRequestsRepository->add(
                $user,
                $printAddress,
                $values->first_name,
                $values->last_name,
                null,
                $values->address,
                $values->number,
                $values->city,
                $values->zip,
                $values->country_id,
                null,
                null,
                null,
                $values->phone_number,
                $this->addressType
            );

            if ($changeRequest) {
                $this->addressChangeRequestsRepository->acceptRequest($changeRequest);
            }
        }

        $this->onSave->__invoke($form, $user);
    }
}
