<?php
declare(strict_types=1);

namespace Crm\PrintModule\Forms;

use Contributte\Translation\Translator;
use Crm\PrintModule\Repositories\PrintClaimsRepository;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Tomaj\Form\Renderer\BootstrapRenderer;

class ClaimFormFactory
{
    /** @var callable */
    public $onCreate;

    /** @var callable */
    public $onUpdate;

    public function __construct(
        private Translator $translator,
        private PrintClaimsRepository $printClaimsRepository,
        private PrintSubscriptionsRepository $printSubscriptionsRepository
    ) {
    }

    public function create(ActiveRow $printSubscription, int $printClaimId = null): Form
    {
        $form = new Form();

        $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();
        $form->setTranslator($this->translator);

        $form->addHidden('id');
        $form->addHidden('print_subscription_id');

        if ($printClaimId) {
            $printClaim = $this->printClaimsRepository->find($printClaimId);
            $defaults = $printClaim->toArray();
        } else {
            $defaults = [
                'print_subscription_id' => $printSubscription->id,
                'claimant' => "{$printSubscription->first_name} {$printSubscription->last_name}",
                'claimant_contact' => $printSubscription->user->email,
                'order_id' => $printSubscription->subscription_id,
            ];
        }

        $form->addTextArea('description', 'print.admin.print_claims.form.fields.description', 80, 5)
            ->setMaxLength(400)
            ->setRequired();

        $form->addText('claimant', 'print.admin.print_claims.form.fields.claimant')
            ->setRequired();

        $form->addText('claimant_contact', 'print.admin.print_claims.form.fields.claimant_contact')
            ->setRequired();

        $form->addSubmit('submit', 'print.admin.print_claims.form.submit');

        $form->setDefaults($defaults);

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if ($values->id) {
            $printClaim = $this->printClaimsRepository->find($values->id);
            $this->printClaimsRepository->update($printClaim, $values);

            ($this->onUpdate)($printClaim);
        } else {
            $printSubscription = $this->printSubscriptionsRepository->find($values->print_subscription_id);

            $printClaim = $this->printClaimsRepository->add(
                $printSubscription,
                $values->description,
                $values->claimant,
                $values->claimant_contact
            );

            ($this->onCreate)($printClaim);
        }
    }
}
