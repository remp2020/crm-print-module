<?php

namespace Crm\PrintModule\DataProviders;

use Contributte\Translation\Translator;
use Crm\ApplicationModule\Models\DataProvider\DataProviderException;
use Crm\PrintModule\Repositories\AddressRedirectsRepository;
use Crm\UsersModule\DataProviders\CanDeleteAddressDataProviderInterface;

class CanDeleteAddressDataProvider implements CanDeleteAddressDataProviderInterface
{
    public function __construct(
        private AddressRedirectsRepository $addressRedirectsRepository,
        private Translator $translator,
    ) {
    }

    public function provide(array $params): array
    {
        if (!isset($params['address'])) {
            throw new DataProviderException('address param missing');
        }

        $addressesRedirectedTo = $this->addressRedirectsRepository
            ->getAddressesRedirectedTo($params['address']->id)
            ->fetchAll();

        if ($addressesRedirectedTo) {
            return [
                'canDelete' => false,
                'message' => $this->translator->translate(
                    'print.admin.address.cant_delete',
                    count($addressesRedirectedTo),
                    [
                        'addresses' => implode(
                            ', ',
                            array_map(fn($a) => '#'. $a->original_address_id, $addressesRedirectedTo)
                        )
                    ]
                )
            ];
        }

        return [
            'canDelete' => true,
        ];
    }
}
