<?php
declare(strict_types=1);

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Components\PreviousNextPaginator\PreviousNextPaginator;
use Crm\PrintModule\Forms\ClaimFormFactory;
use Crm\PrintModule\Repositories\PrintClaimsRepository;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

class ClaimsAdminPresenter extends AdminPresenter
{
    #[Inject]
    public ClaimFormFactory $claimFormFactory;

    #[Inject]
    public PrintClaimsRepository $printClaimsRepository;

    #[Inject]
    public PrintSubscriptionsRepository $printSubscriptionsRepository;

    #[Persistent]
    public $text;

    #[Persistent]
    public $status;

    private const ITEMS_PER_PAGE = 40;

    public function renderDefault(): void
    {
        $printClaims = $this->printClaimsRepository->all($this->text, $this->status);

        $previousNextPaginator = new PreviousNextPaginator();
        $this->addComponent($previousNextPaginator, 'paginator');

        $paginator = $previousNextPaginator->getPaginator();
        $paginator->setItemsPerPage(self::ITEMS_PER_PAGE);

        $printClaims = $printClaims->limit($paginator->getLength(), $paginator->getOffset())->fetchAll();
        $previousNextPaginator->setActualItemCount(count($printClaims));

        $this->template->printClaims = $printClaims;
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

        $form->addSelect('status', 'print.admin.print_claims.filter.fields.status', [
            'closed' => 'print.admin.print_claims.filter.fields.status_close',
            'open' => 'print.admin.print_claims.filter.fields.status_open',
            ])->setPrompt('---');

        $form->addSubmit('send', 'print.admin.print_claims.filter.button')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-filter"></i> Filter');

        $presenter = $this;
        $form->addSubmit('cancel', 'print.admin.print_claims.filter.cancel_button')->onClick[] = function () use ($presenter) {
            $presenter->redirect('ClaimsAdmin:default', [
                'status' => '',
                'text' => '',
            ]);
        };
        $form->onSuccess[] = [$this, 'adminFilterSubmitted'];

        $form->setDefaults((array)$this->params);
        return $form;
    }
}
