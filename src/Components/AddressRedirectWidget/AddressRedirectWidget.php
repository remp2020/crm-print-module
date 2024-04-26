<?php

namespace Crm\PrintModule\Components\AddressRedirectWidget;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\PrintModule\Components\AddressRedirectDetail\AddressRedirectDetailFactoryInterface;
use Crm\PrintModule\Forms\AddressRedirectFormFactory;
use Crm\PrintModule\Repositories\AddressRedirectsRepository;
use Nette\Application\UI\Multiplier;
use Nette\Localization\Translator;
use Nette\Utils\DateTime;

class AddressRedirectWidget extends BaseLazyWidget
{
    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        private readonly AddressRedirectsRepository $addressRedirectsRepository,
        private readonly AddressRedirectDetailFactoryInterface $addressRedirectDetailFactory,
        private readonly AddressRedirectFormFactory $addressRedirectFormFactory,
        private readonly Translator $t,
    ) {
        parent::__construct($lazyWidgetManager);
    }

    public function render($address)
    {
        if (!$address) {
            return;
        }

        if ($address->type !== 'print') {
            return;
        }

        $redirects = $this->addressRedirectsRepository
            ->getAddressRedirects($address->id)->where([
                'to > ?' => new DateTime(),
            ])->fetchAll();

        $this->template->address = $address;
        $this->template->redirects = $redirects;
        $this->template->setFile(__DIR__ . '/' . 'address_redirect_widget.latte');
        $this->template->render();
    }

    public function createComponentRedirectDetail(): Multiplier
    {
        return new Multiplier(function ($addressRedirectId) {
            return $this->addressRedirectDetailFactory->create((int) $addressRedirectId);
        });
    }

    public function createComponentForm(): Multiplier
    {
        return new Multiplier(function ($addressId) {
            $form = $this->addressRedirectFormFactory->create((int) $addressId);
            $form->onError[] = function () use ($addressId) {
                // automatically shows modal if errors are present
                $this->template->openModalForAddressId = $addressId;
            };
            $form->onSuccess[] = function () {
                $this->getPresenter()->flashMessage($this->t->translate('print.component.address_redirect_widget.redirect_saved'));
                $this->getPresenter()->redirect('this');
            };
            return $form;
        });
    }
}
