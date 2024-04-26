<?php

namespace Crm\PrintModule\Components\AddressRedirectDetail;

use Crm\PrintModule\Forms\AddressRedirectFormFactory;
use Crm\PrintModule\Repositories\AddressRedirectsRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use Nette\Utils\DateTime;

class AddressRedirectDetail extends Control
{

    public function __construct(
        private readonly AddressRedirectsRepository $addressRedirectsRepository,
        private readonly AddressRedirectFormFactory $addressRedirectFormFactory,
        private readonly Translator $t,
        private readonly int $addressRedirectId,
    ) {
    }

    public function render()
    {
        $redirect = $this->addressRedirectsRepository->find($this->addressRedirectId);
        if (!$redirect) {
            throw new \RuntimeException("Unable to find address redirect with ID [$this->addressRedirectId]");
        }

        $this->template->redirect = $redirect;
        $this->template->setFile(__DIR__ . '/' . 'address_redirect_detail.latte');
        $this->template->render();
    }

    public function handleTerminateRedirect(): void
    {
        $redirect = $this->addressRedirectsRepository->find($this->addressRedirectId);
        if (!$redirect) {
            throw new \RuntimeException("Unable to find address redirect with ID [$this->addressRedirectId]");
        }

        $nowTrait = new DateTime;

        if ($redirect->from >= $nowTrait) {
            // future redirects are deleted
            $this->addressRedirectsRepository->delete($redirect);
        } else {
            // current redirects are terminated
            $this->addressRedirectsRepository->update($redirect, [
                'to' => new DateTime(),
            ]);
        }

        $this->getPresenter()->flashMessage($this->t->translate('print.component.address_redirect_detail.redirect_terminated'));
        $this->getPresenter()->redirect('this');
    }

    public function createComponentForm(): Form
    {
        $redirect = $this->addressRedirectsRepository->find($this->addressRedirectId);
        if (!$redirect) {
            throw new \RuntimeException("Unable to find address redirect with ID [$this->addressRedirectId]");
        }

        $form = $this->addressRedirectFormFactory->create($redirect->original_address_id, $redirect);
        $form->onError[] = function () {
            // automatically shows modal if errors are present
            $this->template->openModal = true;
        };
        $form->onSuccess[] = function () {
            $this->getPresenter()->flashMessage($this->t->translate('print.component.address_redirect_detail.redirect_updated'));
            $this->getPresenter()->redirect('this');
        };
        return $form;
    }
}
