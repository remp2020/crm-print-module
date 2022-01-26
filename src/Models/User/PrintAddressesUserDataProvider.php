<?php

namespace Crm\PrintModule\User;

use Crm\ApplicationModule\User\UserDataProviderInterface;
use Crm\PrintModule\Repository\PrintSubscriptionsRepository;
use Crm\UsersModule\User\AddressesUserDataProvider;

class PrintAddressesUserDataProvider implements UserDataProviderInterface
{
    private $printSubscriptionsRepository;

    public function __construct(
        PrintSubscriptionsRepository $printSubscriptionsRepository
    ) {
        $this->printSubscriptionsRepository = $printSubscriptionsRepository;
    }

    public static function identifier(): string
    {
        return 'print_addresses';
    }

    public function data($userId)
    {
        return [];
    }

    public function download($userId)
    {
        return [];
    }

    public function downloadAttachments($userId)
    {
        return [];
    }

    public function protect($userId): array
    {
        // Protect addresses that are still used in print exports
        $sql = <<<SQL
SELECT DISTINCT address_id 
  FROM print_subscriptions p1 
  JOIN 
    (SELECT MAX(export_date) AS export_date, type, subscription_id
    FROM print_subscriptions WHERE user_id = ?
    GROUP BY type, subscription_id) p2
  ON p1.export_date = p2.export_date AND p1.type = p2.type AND p1.subscription_id = p2.subscription_id AND p1.user_id = ? AND p1.status != ?
SQL;
        $excludedAddresses = $this->printSubscriptionsRepository->getDatabase()
            ->query($sql, $userId, $userId, PrintSubscriptionsRepository::STATUS_REMOVED)
            ->fetchAssoc('address_id=address_id');
        return [AddressesUserDataProvider::identifier() => array_values($excludedAddresses)];
    }

    public function delete($userId, $protectedData = [])
    {
    }

    public function canBeDeleted($userId): array
    {
        return [true, null];
    }
}
