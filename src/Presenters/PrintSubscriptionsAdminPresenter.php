<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\PrintModule\Models\Config;
use Crm\PrintModule\Models\Export\FilePatternConfig;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Crm\SubscriptionsModule\Repositories\SubscriptionsRepository;
use Crm\UsersModule\Repository\UserActionsLogRepository;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Utils\DateTime;
use Nette\Utils\Finder;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

class PrintSubscriptionsAdminPresenter extends AdminPresenter
{
    #[Inject]
    public PrintSubscriptionsRepository $printSubscriptionsRepository;

    #[Inject]
    public SubscriptionsRepository $subscriptionsRepository;

    #[Inject]
    public FilePatternConfig $filePatternConfig;

    #[Inject]
    public UserActionsLogRepository $userActionsLogRepository;

    #[Persistent]
    public $date;

    public $exportDate;

    #[Persistent]
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

    /**
     * @admin-access-level read
     */
    public function renderDefault($year = null)
    {
        if (!$year) {
            $year = date('Y');
        }

        $years = [];
        $exportList = [];
        $matchedFiles = [];

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
            $exportList = $this->printSubscriptionsRepository->getAllCounts($this->type, $year);

            foreach ($exportList as $date => $export) {
                $dateTime = DateTime::from($date);
                $pattern = $this->filePatternConfig->evaluate($this->type, $dateTime);
                if ($pattern) {
                    $files = Finder::findFiles($pattern)->from(APP_ROOT . '/content/export/');
                    foreach ($files as $file) {
                        $matchedFiles[] = $file->getFilename();
                        $exportList[$date]['files'][] = $file->getFilename();
                    }
                }
            }
        }

        $lastFileDownloads = $this->userActionsLogRepository->getTable()
            ->select('MAX(created_at) AS last_download, JSON_UNQUOTE(JSON_EXTRACT(params, "$.file")) AS file')
            ->where('action = ?', Config::USER_ACTION_PRINT_EXPORT_DOWNLOAD)
            ->where('user_id = ?', $this->getUser()->getId())
            ->where('JSON_UNQUOTE(JSON_EXTRACT(params, "$.file")) IN ?', $matchedFiles)
            ->group('file')
            ->fetchPairs('file', 'last_download');

        $this->template->exportList = $exportList;
        $this->template->years = $years;
        $this->template->actualYear = $year;
        $this->template->lastFileDownloads = $lastFileDownloads;
    }

    /**
     * @admin-access-level read
     */
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
            ->setHtmlAttribute('autofocus');
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
