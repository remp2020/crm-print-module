<?php
declare(strict_types=1);

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Components\PreviousNextPaginator\PreviousNextPaginator;
use Crm\ApplicationModule\Models\Exports\ExcelFactory;
use Crm\PrintModule\Forms\ClaimFormFactory;
use Crm\PrintModule\Models\ClaimType;
use Crm\PrintModule\Models\Export\PrintClaimsExport;
use Crm\PrintModule\Repositories\PrintClaimsRepository;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

class ClaimsAdminPresenter extends AdminPresenter
{
    #[Inject]
    public ClaimFormFactory $claimFormFactory;

    #[Inject]
    public PrintClaimsRepository $printClaimsRepository;

    #[Inject]
    public PrintSubscriptionsRepository $printSubscriptionsRepository;

    #[Inject]
    public ExcelFactory $excelFactory;

    #[Inject]
    public PrintClaimsExport $printClaimsExport;

    #[Persistent]
    public $text;
    #[Persistent]
    public $status;
    #[Persistent]
    public $typeGroup;
    #[Persistent]
    public $from;
    #[Persistent]
    public $to;
    #[Persistent]
    public $claimType;

    private const ITEMS_PER_PAGE = 40;

    private function filterPrintClaims(): Selection
    {
        $to = DateTime::from($this->to)->modify('+1 day')->format('Y-m-d');
        return $this->printClaimsRepository->all($this->text, $this->status, $this->typeGroup, $this->from, $to, $this->claimType);
    }

    public function renderDefault(): void
    {
        $printClaims = $this->filterPrintClaims();

        $previousNextPaginator = new PreviousNextPaginator();
        $this->addComponent($previousNextPaginator, 'paginator');

        $paginator = $previousNextPaginator->getPaginator();
        $paginator->setItemsPerPage(self::ITEMS_PER_PAGE);

        $printClaims = $printClaims->limit($paginator->getLength(), $paginator->getOffset())->fetchAll();
        $previousNextPaginator->setActualItemCount(count($printClaims));

        $this->template->printClaims = $printClaims;
        $this->template->claimPairs = ClaimType::pairs();
    }

    public function handleDownload($format): void
    {
        $excelSpreadSheet = $this->excelFactory->createExcel('print_claims_' . date('y-m-d-h-i'));
        $excelSpreadSheet->getActiveSheet()->setTitle('print_claims_' . date('y-m-d-h-i'));

        $excelSpreadSheet->getActiveSheet()->fromArray($this->printClaimsExport->getExport($this->filterPrintClaims()));

        if ($format === 'CSV') {
            $writer = new Csv($excelSpreadSheet);
            $writer->setDelimiter(';');
            $writer->setUseBOM(true);
            $extension = 'csv';
        } elseif ($format === 'Excel2007') {
            $writer = new Xlsx($excelSpreadSheet);
            $extension = 'xlsx';
        } else {
            throw new \Exception('Unknown export format.');
        }

        $fileName = 'print_claims_' . date('y-m-d-H-i') . '.' . $extension;
        $this->getHttpResponse()->addHeader('Content-Encoding', 'windows-1250');
        $this->getHttpResponse()->addHeader('Content-Type', 'application/octet-stream; charset=windows-1250');
        $this->getHttpResponse()->addHeader('Content-Disposition', "attachment; filename=" . $fileName);

        $response = new CallbackResponse(function () use ($writer) {
            $writer->save("php://output");
        });

        $this->sendResponse($response);
    }

    public function renderShow(int $printClaimId): void
    {
        $printClaim = $this->printClaimsRepository->find($printClaimId);
        if (!$printClaim) {
            throw new BadRequestException("Print claim not found (id: {$printClaimId})");
        }

        $this->template->printClaim = $printClaim;
        $this->template->printSubscription = $printClaim->print_subscription;
        $this->template->claimPairs = ClaimType::pairs();
    }

    public function renderNew(int $printSubscriptionId): void
    {
        $printSubscription = $this->printSubscriptionsRepository->find($printSubscriptionId);
        if (!$printSubscription) {
            throw new BadRequestException("Print subscription not found (id: {$printSubscriptionId})");
        }

        $this->template->printSubscription = $printSubscription;
    }

    public function renderEdit(int $printClaimId): void
    {
        $printClaim = $this->printClaimsRepository->find($printClaimId);
        if (!$printClaim) {
            throw new BadRequestException("Print claim not found (id: {$printClaimId})");
        }

        $this->template->printClaim = $printClaim;
        $this->template->printSubscription = $printClaim->print_subscription;
    }

    public function handleCloseClaim(int $printClaimId): void
    {
        $printClaim = $this->printClaimsRepository->find($printClaimId);
        if (!$printClaim) {
            throw new BadRequestException("Print claim not found (id: {$printClaimId})");
        }
        $this->printClaimsRepository->close($printClaim);

        $this->flashMessage($this->translator->translate('print.admin.print_claims.default.close_success_message'));
    }

    public function createComponentClaimForm(): Form
    {
        $printClaimId = (int)$this->getParameter('printClaimId');

        if ($printClaimId) {
            $printClaim = $this->printClaimsRepository->find($printClaimId);
            $printSubscription = $printClaim->print_subscription;
        } else {
            $printSubscription = $this->printSubscriptionsRepository->find($this->getParameter('printSubscriptionId'));
            if (!$printSubscription) {
                throw new BadRequestException('Invalid print subscription ID provided: ' . $this->getParameter('printSubscriptionId'));
            }
        }

        $form = $this->claimFormFactory->create($printSubscription, $printClaimId);
        $this->claimFormFactory->onCreate = function () {
            $this->flashMessage($this->translator->translate('print.admin.print_claims.form.add_success_message'));
            $this->redirect('default');
        };
        $this->claimFormFactory->onUpdate = function () {
            $this->flashMessage($this->translator->translate('print.admin.print_claims.form.edit_success_message'));
            $this->redirect('default');
        };

        return $form;
    }

    public function createComponentFilterForm()
    {
        $form = new Form;
        $form->setRenderer(new BootstrapInlineRenderer());
        $form->setTranslator($this->translator);

        $form->addText('text', 'print.admin.print_claims.filter.fields.name.label')
            ->setHtmlAttribute('placeholder', 'print.admin.print_claims.filter.fields.name.placeholder')
            ->setHtmlAttribute('autofocus');

        $form->addSelect('claimType', 'print.admin.print_claims.filter.fields.claim_type', ClaimType::pairs())
            ->setPrompt('---');

        $form->addSelect('status', 'print.admin.print_claims.filter.fields.status', [
            'closed' => 'print.admin.print_claims.filter.fields.status_close',
            'open' => 'print.admin.print_claims.filter.fields.status_open',
            ])->setPrompt('---');

        $form->addSelect(
            'typeGroup',
            'print.admin.print_claims.filter.fields.type_group',
            $this->getTypeGroupValues()
        )->setPrompt('---');

        $form->addText('from', 'print.admin.print_claims.filter.fields.from')
            ->setHtmlAttribute('class', 'flatpickr');

        $form->addText('to', 'print.admin.print_claims.filter.fields.to')
            ->setHtmlAttribute('class', 'flatpickr');

        $form->addSubmit('send', 'print.admin.print_claims.filter.button')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-filter"></i> Filter');

        $presenter = $this;
        $form->addSubmit('cancel', 'print.admin.print_claims.filter.cancel_button')->onClick[] = function () use ($presenter) {
            $presenter->redirect('ClaimsAdmin:default', [
                'status' => '',
                'text' => '',
                'claimType' => null,
                'typeGroup' => null,
                'from' => null,
                'to' => null,
            ]);
        };
        $form->onSuccess[] = [$this, 'adminFilterSubmitted'];

        $form->setDefaults((array)$this->params);
        return $form;
    }

    private function getTypeGroupValues(): array
    {
        return $this->printSubscriptionsRepository->getDatabase()
            ->query(<<<SQL
SELECT SUBSTRING_INDEX(`type`, '_', 1) AS `type_group` 
FROM (
  SELECT DISTINCT type
  FROM `print_subscriptions` 
) t1
GROUP BY 1
SQL)->fetchPairs('type_group', 'type_group');
    }
}
