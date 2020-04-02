<?php

namespace Crm\PrintModule\Export;

use Crm\PrintModule\Repository\PrintSubscriptionsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Crm\UsersModule\Repository\AddressChangeRequestsRepository;
use Crm\UsersModule\Repository\AddressesMetaRepository;
use Crm\UsersModule\Repository\AddressesRepository;
use Nette\Utils\Json;

class ExportEngine
{
    private $addressesRepository;

    private $addressesMetaRepository;

    private $printSubscriptionsRepository;

    private $subscriptionsRepository;

    public function __construct(
        AddressesRepository $addressesRepository,
        AddressesMetaRepository $addressesMetaRepository,
        PrintSubscriptionsRepository $printSubscriptionsRepository,
        SubscriptionsRepository $subscriptionsRepository
    ) {
        $this->addressesRepository = $addressesRepository;
        $this->addressesMetaRepository = $addressesMetaRepository;
        $this->printSubscriptionsRepository = $printSubscriptionsRepository;
        $this->subscriptionsRepository = $subscriptionsRepository;
    }

    public function run(ExportCriteria $criteria, SourceInterface $source, ViewInterface $view, $sharedMeta = [])
    {
        $data = $source->loadData($criteria);

        $printExportDate = $criteria->getExportTo();

        foreach ($data as $row) {
            $user = $row->user;

            if (in_array($criteria->getKey(), ['tyzden_daily', 'tyzden_print_special', 'tyzden_print_new'])) {
                $address = $row->address;
            } elseif ($criteria->getKey() == 'dennikn_magazine') {
                $address = $this->addressesRepository->address($user, 'magazine_print');
                if (!$address) {
                    $address = $this->addressesRepository->address($user, 'print');
                }
            } else {
                $address = $row->address;
            }

            if (!$address) {
                $address = $this->addressesRepository->address($user, 'print_other');
            }
            if (!$address) {
                $address = $this->addressesRepository->address($user, 'print');
            }

            if (!$address) {
                $address = null;
            }

            if (!$address && in_array($criteria->getKey(), ['dennikn_daily', 'dennikn_friday', 'dennikn_magazine', 'print_daily', 'print_friday', 'future_print_friday'])) {
                continue;
            }

            $meta = $sharedMeta;
            if ($address) {
                $changeRequest = $address->related('address_change_requests')
                    ->where([
                        'status' => AddressChangeRequestsRepository::STATUS_ACCEPTED,
                    ])
                    ->order('updated_at DESC')
                    ->limit(1)
                    ->fetch();
                if ($changeRequest) {
                    $meta = array_merge($sharedMeta, $this->addressesMetaRepository->values($address, $changeRequest)->fetchPairs('key', 'value'), [
                        'address_change_request_id' => $changeRequest->id,
                    ]);
                }
            }

            $this->printSubscriptionsRepository->add($criteria->getKey(), $row->id, $user, $address, $printExportDate, 'new', $criteria->getExportAt(), Json::encode($meta));
        }

        if ($criteria->getKey() == 'tyzden_daily') {
            $rows = $this->printSubscriptionsRepository->getExportData($criteria->getKey(), $printExportDate);
            foreach ($rows as $row) {
                $subscription = $row->subscription;
                if ($subscription->created_at->format('Y-m-d') != $subscription->modified_at->format('Y-m-d')) {
                    $this->printSubscriptionsRepository->update($row, ['status' => 'updated']);
                }
            }
        } elseif (in_array($criteria->getKey(), ['tyzden_print_special'])) {
            $this->printSubscriptionsRepository->setPrintExportStatusTyzdenSpecial($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        } elseif (in_array($criteria->getKey(), ['tyzden_print_new'])) {
            $this->printSubscriptionsRepository->setPrintExportStatusTyzdenDaily($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        } else {
            $this->printSubscriptionsRepository->setPrintExportStatus($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        }

        $rows = $this->printSubscriptionsRepository->getExportData($criteria->getKey(), $printExportDate);

        return $view->generateExportFile($criteria, $rows);
    }
}
