<?php

namespace Crm\PrintModule\User;

use Crm\ApplicationModule\User\UserDataProviderInterface;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;

class AddressChangeRequestsUserDataProvider implements UserDataProviderInterface
{
    private $addressChangeRequestsRepository;

    public function __construct(AddressChangeRequestsRepository $addressChangeRequestsRepository)
    {
        $this->addressChangeRequestsRepository = $addressChangeRequestsRepository;
    }

    public static function identifier(): string
    {
        return 'address_change_requests';
    }

    public function data($userId): ?array
    {
        return null;
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
        return [];
    }

    public function delete($userId, $protectedData = [])
    {
        $this->addressChangeRequestsRepository->deleteAll($userId);
    }

    public function canBeDeleted($userId): array
    {
        return [true, null];
    }
}
