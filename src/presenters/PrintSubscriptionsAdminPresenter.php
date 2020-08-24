<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\PrintModule\Repository\PrintSubscriptionsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Nette\Application\UI\Form;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

class PrintSubscriptionsAdminPresenter extends AdminPresenter
{
    /** @var  PrintSubscriptionsRepository @inject */
    public $printSubscriptionsRepository;

    /** @var  SubscriptionsRepository @inject */
    public $subscriptionsRepository;

    /** @persistent */
    public $date;

    public $exportDate;

    /** @persistent */
    public $type;

    public function startup()
    {
        parent::startup();
        $this->text = isset($this->params['text']) ? $this->params['text'] : null;
        $this->exportDate = isset($this->params['date']) ? $this->params['date'] : null;
        $types = $this->printSubscriptionsRepository->getTypes();

        if (isset($this->params['type'])) {
            $this->type = $this->params['type'];
        } else {
            $this->type = count($types) ? array_keys($types)[0] : null;
        }
        $this->template->types = $types;
        $this->template->type = $this->type;
    }

    public function renderDefault($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $years = [];
        if ($this->type) {
            $firstExport = $this->printSubscriptionsRepository->firstExport($this->type);
            $lastExport = $this->printSubscriptionsRepository->lastExport($this->type);
            $startDate = date('Y');
            $endDate = date('Y');
            if ($firstExport) {
                $startDate = (int) $firstExport->export_date->format('Y');
            }
            if ($lastExport) {
                $endDate = (int) $lastExport->export_date->format('Y');
            }
            while ($startDate <= $endDate) {
                $years[] = $startDate;
                $startDate++;
            }
            rsort($years);
            $this->template->exportList = $this->printSubscriptionsRepository->getAllCounts($this->type, $year);
        } else {
            $this->template->exportList = [];
        }

        $this->template->years = $years;
        $this->template->actualYear = $year;
    }

    public function renderShow($date)
    {
        $this->template->date = $date;
        $printExports = $this->printSubscriptionsRepository->getExport($this->type, $date, $this->text);
        $this->template->printSubscriptions = $printExports;
        $this->template->printSubscriptionsCount = $printExports->count('*');
    }

    public function createComponentAdminFilterForm()
    {
        $form = new Form;
        $form->setRenderer(new BootstrapInlineRenderer());
        $form->addText('text', $this->translator->translate('print.component.admin_filter.text'))
            ->setAttribute('autofocus');
        $form->addSubmit('send', $this->translator->translate('print.component.admin_filter.filter'))
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-filter"></i> ' . $this->translator->translate('print.component.admin_filter.filter'));
        $form->addHidden('date', $this->params['date']);
        $presenter = $this;
        $form->addSubmit('cancel', $this->translator->translate('print.component.admin_filter.cancel_filter'))->onClick[] = function () use ($presenter) {
            $presenter->redirect('PrintSubscriptionsAdmin:show', ['date' => $this->exportDate, 'text' => '']);
        };
        $form->onSuccess[] = [$this, 'adminFilterSubmited'];
        $form->setDefaults([
            'date' => $this->exportDate,
            'text' => $this->text,
        ]);
        return $form;
    }

    public function adminFilterSubmited($form, $values)
    {
        $this->redirect('show', [
            'date' => $values['date'],
            'text' => $values['text'],
        ]);
    }
}
