<?php

namespace Crm\PrintModule\Components\DefaultPrintAddressWidget;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Localization\Translator;

class DefaultPrintAddressWidget extends BaseLazyWidget
{
    private string $templateName = 'default_print_address_widget.latte';

    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        private readonly AddressesRepository $addressesRepository,
        private readonly Translator $translator,
    ) {
        parent::__construct($lazyWidgetManager);
    }

    public function identifier()
    {
        return 'defaultprintaddresswidget';
    }

    public function render($address)
    {
        if ($address->type !== 'print' || $address->is_default) {
            return;
        }
        $this->template->addressId = $address->id;
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . $this->templateName);
        $this->template->render();
    }

    public function handleSetDefaultPrintAddress($addressId)
    {
        $address = $this->addressesRepository->find($addressId);
        $this->setDefaultAddress($address);
        $this->getPresenter()->flashMessage($this->translator->translate(
            'print.component.default_print_address_widget.change_success'
        ));
        $this->redirect('this');
    }

    private function setDefaultAddress(ActiveRow $address): bool
    {
        $defaultAddress = $this->addressesRepository->address($address->user, $address->type, true);
        if ($defaultAddress) {
            $this->addressesRepository->update($defaultAddress, ['is_default' => false]);
        }

        return $this->addressesRepository->update($address, ['is_default' => true]);
    }
}
