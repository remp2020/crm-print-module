<?php declare(strict_types=1);

namespace Crm\PrintModule\DataProviders\SubscriptionTransfer;

use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Database\Table\ActiveRow;

class AddressToTransferRetriever
{
    public function __construct(
        private readonly AddressesRepository $addressesRepository,
    ) {
    }

    public function retrieve(ActiveRow $subscription): ?ActiveRow
    {
        $address = $subscription->address;

        $hasAssignedPrintAddress = $address?->type === 'print';
        if ($hasAssignedPrintAddress) {
            return $address;
        }

        return $this->addressesRepository->address($subscription->user, 'print');
    }
}
