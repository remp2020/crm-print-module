<?php
declare(strict_types=1);

namespace Crm\PrintModule\Components\ClaimButtonWidget;

use Crm\ApplicationModule\Models\Database\ActiveRow;
use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;

class ClaimButtonWidget extends BaseLazyWidget
{
    private string $templateName = 'claim_button_widget.latte';

    private array $allowedExportTypes = [];

    public function identifier(): string
    {
        return 'printmoduleclaimbuttonwidget';
    }

    public function setAllowedExportTypes(string $type): void
    {
        $this->allowedExportTypes[] = $type;
    }

    public function render(ActiveRow $printSubscription): void
    {
        if ($printSubscription->status === PrintSubscriptionsRepository::STATUS_REMOVED) {
            return;
        }

        if (!in_array($printSubscription->type, $this->allowedExportTypes, true)) {
            return;
        }

        $this->template->printSubscription = $printSubscription;
        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }
}
