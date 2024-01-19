<?php

namespace Crm\PrintModule\Components\UserPrintExport;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;

/**
 * This widgets fetches all users exported print subscriptions
 * and renders bootstrap table.
 *
 * @package Crm\PrintModule\Components
 */
class UserPrintExport extends BaseLazyWidget
{
    private $templateName = 'user_print_export.latte';

    private $totalCount;

    protected $printSubscriptionsRepository;

    private $translator;

    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        PrintSubscriptionsRepository $printSubscriptionsRepository,
        Translator $translator
    ) {
        parent::__construct($lazyWidgetManager);
        $this->printSubscriptionsRepository = $printSubscriptionsRepository;
        $this->translator = $translator;
    }

    public function header($id = '')
    {
        $header = $this->translator->translate('print.component.user_print_export.header');
        if ($id) {
            $header .= ' <small>(' . $this->totalCount($id) . ')</small>';
        }
        return $header;
    }

    public function identifier()
    {
        return 'printexports';
    }

    public function render($id = '')
    {
        $this->template->userPrintExports = $this->printSubscriptionsRepository->userPrintSubscriptions($id);
        $this->template->totalCount = $this->totalCount($id);

        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }

    private function totalCount($id)
    {
        if ($this->totalCount == null) {
            $this->totalCount = $this->printSubscriptionsRepository->userPrintSubscriptions($id)->count('*');
        }
        return $this->totalCount;
    }
}
