<?php

namespace Crm\PrintModule\Forms;

use Crm\PrintModule\Repositories\AddressRedirectsRepository;
use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Application\UI\Form;
use Nette\Localization\Translator;
use Nette\Utils\DateTime;
use Tomaj\Form\Renderer\BootstrapVerticalRenderer;

class AddressRedirectFormFactory
{
    public function __construct(
        private readonly Translator $translator,
        private readonly AddressesRepository $addressesRepository,
        private readonly AddressRedirectsRepository $addressRedirectsRepository,
    ) {
    }

    public function create($addressId, $redirectId = null): Form
    {
        $defaults = [];

        $address = $this->addressesRepository->find($addressId);
        if (!$address) {
            throw new \RuntimeException("Unable to find address with ID [$addressId]");
        }
        $redirect = $redirectId ? $this->addressRedirectsRepository->find($redirectId) : null;

        $now = new DateTime();
        $enableFromEditing = true;
        $enableToEditing = true;

        if ($redirect) {
            $defaults = $redirect->toArray();
            $enableFromEditing = $redirect->from > $now;
            $enableToEditing = $redirect->to > $now;
        }

        $form = new Form();
        $form->setRenderer(new BootstrapVerticalRenderer);
        $form->setTranslator($this->translator);
        $form->addProtection();

        $form->addHidden('address_id', $addressId);

        if ($redirectId) {
            $form->addHidden('redirect_id', $redirectId->id);
        }

        $addresses = $this->addressesRepository->addressesSelect($address->user, 'print');
        unset($addresses[$address->id]); // do not allow redirect to same address

        $form->addSelect(
            'redirect_address_id',
            $this->translator->translate('print.admin.address_redirect_form.new_address'),
            $addresses
        )
            ->setRequired('print.admin.address_redirect_form.input_required')
            ->setPrompt('--');

        $from = $form->addText('from', $this->translator->translate('print.admin.address_redirect_form.from'))
            ->setRequired('print.admin.address_redirect_form.input_required')
            ->setHtmlAttribute('class', 'flatpickr')
            ->setHtmlAttribute('flatpickr_datetime_seconds', "1");
        if ($enableFromEditing) {
            $from->setHtmlAttribute('flatpickr_mindate', 'today')
                ->setOption('description', 'print.admin.address_redirect_form.description.from');
        } else {
            $from->setDisabled();
        }

        $to = $form->addText('to', $this->translator->translate('print.admin.address_redirect_form.to'))
            ->setRequired('print.admin.address_redirect_form.input_required')
            ->setHtmlAttribute('class', 'flatpickr')
            ->setHtmlAttribute('flatpickr_mindate', 'today')
            ->setHtmlAttribute('flatpickr_datetime_seconds', "1");
        if ($enableToEditing) {
            $to->setHtmlAttribute('flatpickr_mindate', 'today')
                ->setOption('description', 'print.admin.address_redirect_form.description.from');
        } else {
            $to->setDisabled();
        }

        $form->addText('note', $this->translator->translate('print.admin.address_redirect_form.note'));
        $form->addSubmit('submit', $this->translator->translate('print.admin.address_redirect_form.save'));
        $form->onSuccess[] = [$this, 'formSucceeded'];
        $form->onValidate[] = [$this, 'formValidate'];
        $form->setDefaults($defaults);

        return $form;
    }

    public function formSucceeded(Form $form, array $values): void
    {
        [$from, $to] = $this->getRedirectInterval($values);

        $now = new DateTime();
        // do not allow past dates in $from and $to values
        if ($from < $now) {
            $from = $now;
        }
        if ($to < $now) {
            $to = $now;
        }

        if (isset($values['redirect_id'])) {
            $redirect = $this->addressRedirectsRepository->find($values['redirect_id']);
            $data = [
                'redirect_address_id' => $values['redirect_address_id'],
                'note' => $values['note'] ?? null,
            ];
            // allow updating only future dates
            if ($redirect->from > $now) {
                $data['from'] = $from;
            }
            if ($redirect->to > $now) {
                $data['to'] = $to;
            }

            $this->addressRedirectsRepository->update($redirect, $data);
        } else {
            $this->addressRedirectsRepository->add(
                originalAddress: $this->addressesRepository->find($values['address_id']),
                redirectAddress: $this->addressesRepository->find($values['redirect_address_id']),
                from: $from,
                to: $to,
                note: $values['note'] ?: null,
            );
        }
    }

    private function getRedirectInterval($values)
    {
        $redirect = null;
        if (isset($values['redirect_id'])) {
            $redirect = $this->addressRedirectsRepository->find($values['redirect_id']);
        }

        if (!isset($values['from']) && $redirect) {
            $from = $redirect->from;
        } else {
            $from = DateTime::from($values['from']);
        }

        if (!isset($values['to']) && $redirect) {
            $to = $redirect->from;
        } else {
            $to = DateTime::from($values['to']);
        }
        return [$from, $to];
    }

    public function formValidate(Form $form, $values): void
    {
        if ($values['address_id'] === $values['redirect_address_id']) {
            $form['redirect_address_id']->addError('print.admin.address_redirect_form.error.same_address_redirect');
        }

        [$from, $to] = $this->getRedirectInterval($values);

        $now = new DateTime();

        if ($from >= $to) {
            $form['to']->addError('print.admin.address_redirect_form.error.end_time_earlier_than_start');
        }

        if ($to <= $now) {
            $form['to']->addError('print.admin.address_redirect_form.error.end_time_has_to_in_future');
        }

        // $from is converted to NOW if set to past date
        if ($from < $now) {
            $from = $now;
        }

        $q = $this->addressRedirectsRepository->getAddressRedirects($values['address_id'])
            ->whereOr([
                'from BETWEEN ? AND ?' => [$from, $to],
                'to BETWEEN ? AND ?' => [$from, $to],
                'from <= ? AND to >= ?' => [$from, $to],
            ]);
        if (isset($values['redirect_id'])) {
            $q->where('id != ?', $values['redirect_id']);
        }

        $overlappingRedirect = $q->fetch();
        if ($overlappingRedirect) {
            $form['to']->addError('print.admin.address_redirect_form.error.redirect_times_overlap');
        }
    }
}
