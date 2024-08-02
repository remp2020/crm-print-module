<?php

namespace Crm\PrintModule\Forms;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Forms\Controls\CountriesSelectItemsBuilder;
use Crm\ApplicationModule\Models\DataProvider\DataProviderManager;
use Crm\UsersModule\DataProviders\AddressFormDataProviderInterface;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;
use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;
use Tomaj\Form\Renderer\BootstrapRenderer;

class UserPrintAddressFormFactory
{
    /* callback function */
    public $onSave;

    /** @var ActiveRow */
    private $payment;

    private ?ActiveRow $printAddress;

    private $addressType;

    public function __construct(
        private readonly Translator $translator,
        private readonly AddressesRepository $addressesRepository,
        private readonly AddressChangeRequestsRepository $addressChangeRequestsRepository,
        private readonly DataProviderManager $dataProviderManager,
        private readonly CountriesSelectItemsBuilder $countriesSelectItemsBuilder,
    ) {
    }

    public function create(ActiveRow $payment, string $addressType = 'print'): Form
    {
        $this->addressType = $addressType;

        $form = new Form;

        $this->payment = $payment;
        $user = $this->payment->user;

        $printAddress = null;
        if ($payment->address_id !== null && $payment->address->type === 'print') {
            $printAddress = $payment->address;
        }
        if (!$printAddress) {
            $printAddress = $this->addressesRepository->address($user, $this->addressType, true);
        }
        $this->printAddress = $printAddress;

        $countryPairs = $this->countriesSelectItemsBuilder->getDefaultCountryPair();
        if ($printAddress) {
            $countryPairs[$printAddress->country->id] = $printAddress->country->name;
        }

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
        $form->addSelect('country_id', 'print.form.print_address.label.country_id', $countryPairs)
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
                'country_id' => $printAddress->country_id,
            ]);
        }

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];

        /** @var AddressFormDataProviderInterface[] $providers */
        $providers = $this->dataProviderManager->getProviders('sales_funnel.dataprovider.address_form', AddressFormDataProviderInterface::class);
        foreach ($providers as $sorting => $provider) {
            $form = $provider->provide(['form' => $form, 'payment' => $payment, 'address' => $printAddress, 'self' => $this, 'addressType' => 'print']);
        }

        $form->onSuccess[] = [$this, 'formSucceededAfterProviders'];

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        $user = $this->payment->user;

        if (isset($values['first_name'])) {
            $changeRequest = $this->addressChangeRequestsRepository->add(
                user: $user,
                parentAddress: $this->printAddress,
                firstName: $values->first_name,
                lastName: $values->last_name,
                companyName: null,
                address: $values->address,
                number: $values->number,
                city: $values->city,
                zip: $values->zip,
                countryId: $values->country_id,
                companyId: null,
                companyTaxId: null,
                companyVatId: null,
                phoneNumber: $values->phone_number,
                type: $this->addressType
            );

            if ($changeRequest) {
                $this->addressChangeRequestsRepository->acceptRequest($changeRequest);
            }
        }
    }

    public function formSucceededAfterProviders(Form $form, $values): void
    {
        $this->onSave->__invoke($form, $this->payment->user);
    }
}
