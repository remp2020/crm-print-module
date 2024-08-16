<?php declare(strict_types=1);

namespace Crm\PrintModule\DataProviders;

use Crm\ApplicationModule\Models\DataProvider\DataProviderException;
use Crm\PrintModule\DataProviders\SubscriptionTransfer\AddressToTransferRetriever;
use Crm\SubscriptionsModule\DataProviders\SubscriptionTransferDataProviderInterface;
use Crm\SubscriptionsModule\Repositories\SubscriptionsRepository;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;
use Crm\UsersModule\Repositories\AddressesMetaRepository;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;

class SubscriptionTransferFormDataProvider implements SubscriptionTransferDataProviderInterface
{
    public function __construct(
        private readonly AddressToTransferRetriever $addressToTransferRetriever,
        private readonly AddressChangeRequestsRepository $addressChangeRequestsRepository,
        private readonly AddressesMetaRepository $addressesMetaRepository,
        private readonly SubscriptionsRepository $subscriptionsRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function provide(array $params): void
    {
        if (!isset($params['form'])) {
            throw new DataProviderException('form param missing');
        }
        if (!isset($params['subscription'])) {
            throw new DataProviderException('subscription param missing');
        }

        $form = $params['form'];
        $subscription = $params['subscription'];

        $address = $this->addressToTransferRetriever->retrieve($subscription);
        if ($address !== null) {
            $form->addCheckbox('copy_print_address', 'print.admin.subscription_transfer.copy_address');
        }
    }

    public function transfer(ActiveRow $subscription, ActiveRow $userToTransferTo, ArrayHash $formData): void
    {
        $address = $this->addressToTransferRetriever->retrieve($subscription);
        if ($address === null) {
            return;
        }

        if (!$formData->copy_print_address) {
            return;
        }

        [$copiedAddress, $addressChangeRequest] = $this->copyAddress($userToTransferTo, $address);

        $this->copyAddressMeta($address, $copiedAddress, $addressChangeRequest);

        $this->subscriptionsRepository->update($subscription, [
            'address_id' => $copiedAddress->id,
        ]);
    }

    public function isTransferable(ActiveRow $subscription): bool
    {
        return true;
    }

    /**
     * @return array{ActiveRow, ActiveRow}
     */
    private function copyAddress(ActiveRow $userToTransferTo, ActiveRow $address): array
    {
        $addressChangeRequest = $this->addressChangeRequestsRepository->add(
            $userToTransferTo,
            parentAddress: false,
            firstName: $address->first_name,
            lastName: $address->last_name,
            companyName: $address->company_name,
            address: $address->address,
            number: $address->number,
            city: $address->city,
            zip: $address->zip,
            countryId: $address->country_id,
            companyId: $address->company_id,
            companyTaxId: $address->company_tax_id,
            companyVatId: $address->company_vat_id,
            phoneNumber: $address->phone_number,
            type: $address->type,
        );

        $address = $this->addressChangeRequestsRepository->acceptRequest($addressChangeRequest);

        return [$address, $addressChangeRequest];
    }

    private function copyAddressMeta(ActiveRow $sourceAddress, ActiveRow $copiedAddress, ActiveRow $addressChangeRequest): void
    {
        $sourceAddressChangeRequest = $this->addressChangeRequestsRepository->lastAcceptedForAddress($sourceAddress);
        $sourceAddressMetas = $sourceAddressChangeRequest->related('addresses_meta')->fetchAll();

        foreach ($sourceAddressMetas as $sourceAddressMeta) {
            $this->addressesMetaRepository->add(
                $copiedAddress,
                $addressChangeRequest,
                $sourceAddressMeta->key,
                $sourceAddressMeta->value,
            );
        }
    }
}
