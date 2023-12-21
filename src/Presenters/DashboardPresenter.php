<?php

namespace Crm\PrintModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\SubscriptionsModule\Repository\ContentAccessRepository;
use Nette\DI\Attributes\Inject;
use Nette\Utils\DateTime;

class DashboardPresenter extends AdminPresenter
{
    #[Inject]
    public ContentAccessRepository $contentAccessRepository;

    /**
     * @admin-access-level read
     */
    public function renderDefault()
    {
        $contentAccess = $this->contentAccessRepository->findBy('name', 'print');

        $within30days = $this->contentAccessRepository
            ->usersWithAccessActiveBetween($contentAccess, DateTime::from('-30 days'), DateTime::from('now'))
            ->count('DISTINCT(users.id)');

        $within90days = $this->contentAccessRepository
            ->usersWithAccessActiveBetween($contentAccess, DateTime::from('-90 days'), DateTime::from('now'))
            ->count('DISTINCT(users.id)');

        $notWithin90days = $this->contentAccessRepository
            ->usersWithAccessActiveBetween($contentAccess, DateTime::from('@233431200'), DateTime::from('-90 days'))
            ->count('DISTINCT(users.id)');

        $this->template->within30days = $within30days;
        $this->template->within90days = $within90days;
        $this->template->notWithin90Days = $notWithin90days;
    }
}
