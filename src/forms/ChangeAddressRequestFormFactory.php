<?php

namespace Crm\PrintModule\Forms;

use Crm\UsersModule\Repository\AddressChangeRequestsRepository;
use Crm\UsersModule\Repository\AddressesRepository;
use Crm\UsersModule\Repository\CountriesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use League\Event\Emitter;
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

    private $usersRepository;

    private $addressChangeRequestsRepository;

    private $addressesRepository;

    private $countriesRepository;

    private $emitter;

    private $translator;

    public function __construct(
        UsersRepository $usersRepository,
        AddressChangeRequestsRepository $addressChangeRequestsRepository,
        AddressesRepository $addressesRepository,
        CountriesRepository $countriesRepository,
        Emitter $emitter,
        Translator $translator
    ) {
        $this->usersRepository = $usersRepository;
        $this->addressChangeRequestsRepository = $addressChangeRequestsRepository;
        $this->addressesRepository = $addressesRepository;
        $this->countriesRepository = $countriesRepository;
        $this->emitter = $emitter;
        $this->translator = $translator;
    }

    /**
     * @params $user
     * @return Form
     */
    public function create(User $user)
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

        $userRow = $this->usersRepository->find($user->id);
        if ($this->addressesRepository->address($userRow, 'print')) {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_update'));
        } else {
            $form->addSubmit('send', $this->translator->translate('print.frontend.change_address_request_form.submit_create'));
        }

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $userRow = $this->loadUserRow();

        $printAddress = $this->addressesRepository->address($userRow, 'print');

        $request = $this->addressChangeRequestsRepository->add(
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
            $this->onUserUpdate->__invoke($form, $request);
            return;
        }

        $this->onSuccessRequest->__invoke($form, $request);
    }

    private function loadUserRow()
    {
        $row = $this->usersRepository->find($this->user->id);
        if (!$row) {
            throw new BadRequestException();
        }
        return $row;
    }
}
