<?php

namespace Crm\PrintModule\Models\Export;

use Crm\PrintModule\Repositories\AddressRedirectsRepository;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Crm\SubscriptionsModule\Repositories\SubscriptionMetaRepository;
use Crm\UsersModule\Repositories\AddressChangeRequestsRepository;
use Crm\UsersModule\Repositories\AddressesMetaRepository;
use Crm\UsersModule\Repositories\AddressesRepository;
use Nette\Utils\Json;

class ExportEngine
{
    public function __construct(
        private readonly AddressesRepository $addressesRepository,
        private readonly AddressesMetaRepository $addressesMetaRepository,
        private readonly AddressRedirectsRepository $addressRedirectsRepository,
        private readonly PrintSubscriptionsRepository $printSubscriptionsRepository,
        private readonly SubscriptionMetaRepository $subscriptionMetaRepository,
    ) {
    }

    public function run(ExportCriteria $criteria, SourceInterface $source, ViewInterface $view, $sharedMeta = [])
    {
        $data = $source->loadData($criteria);

        $printExportDate = $criteria->getExportTo();

        foreach ($data as $row) {
            // if only new subscribers should be processed, check if subscription already has a record
            if ($criteria->getBackIssues()
                && $this->printSubscriptionsRepository->getTable()
                    ->where([
                        'subscription_id' => $row->id,
                    ])->fetch()
            ) {
                continue;
            }

            $user = $row->user;

            if (in_array($criteria->getKey(), ['tyzden_daily', 'tyzden_print_special', 'tyzden_print_new'], true)) {
                $address = $row->address;
            } elseif (in_array($criteria->getKey(), ['dennikn_magazine', 'dennikn_magazine_preliminary', 'dennikn_heroine', 'dennikn_heroine_preliminary'], true)) {
                $address = $this->addressesRepository->address($user, 'magazine_print');
                if (!$address) {
                    $address = $this->addressesRepository->address($user, 'print', true)
                        ?? $this->addressesRepository->address($user, 'print');
                }
            } else {
                $address = $row->address;
            }

            if (!$address) {
                $address = $this->addressesRepository->address($user, 'print_other');
            }
            if (!$address) {
                $address = $this->addressesRepository->address($user, 'print', true)
                    ?? $this->addressesRepository->address($user, 'print');
            }

            if (!$address) {
                $address = null;
            }

            if (!$address &&
                !in_array($criteria->getKey(), ['tyzden_daily', 'tyzden_print_special', 'tyzden_print_new'], true)
            ) {
                continue;
            }

            if ($address) {
                if (!$criteria->shouldDeliverToCountry($address->country->iso_code)) {
                    continue;
                }

                $redirect = $this->addressRedirectsRepository->getAddressCurrentRedirect($address->id)->fetch();
                if ($redirect) {
                    $address = $redirect->redirect_address;
                }
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

            // save also subscription meta
            $meta = array_merge($meta, $this->subscriptionMetaRepository->subscriptionMeta($row));

            $this->printSubscriptionsRepository->add(
                type: $criteria->getKey(),
                subscriptionId: $row->id,
                user: $user,
                exportDate: $printExportDate,
                address: $address,
                exportAt: $criteria->getExportAt(),
                meta: Json::encode($meta),
            );
        }

        if ($criteria->hasChangeStatusCallback()) {
            $criteria->callChangeStatusCallback($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        } elseif ($criteria->getKey() == 'tyzden_daily') {
            $rows = $this->printSubscriptionsRepository->getExportData($criteria->getKey(), $printExportDate);
            foreach ($rows as $row) {
                $subscription = $row->subscription;
                if ($subscription->created_at->format('Y-m-d') != $subscription->modified_at->format('Y-m-d')) {
                    $this->printSubscriptionsRepository->update($row, ['status' => 'updated']);
                }
            }
        } elseif (in_array($criteria->getKey(), ['tyzden_print_special'], true)) {
            $this->printSubscriptionsRepository->setPrintExportStatusTyzdenSpecial($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        } elseif (in_array($criteria->getKey(), ['tyzden_print_new'], true)) {
            $this->printSubscriptionsRepository->setPrintExportStatusTyzdenDaily($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        } else {
            $this->printSubscriptionsRepository->setPrintExportStatus($criteria->getKey(), $printExportDate, $criteria->getExportAt());
        }

        $rows = $this->printSubscriptionsRepository->getExportData($criteria->getKey(), $printExportDate);

        return $view->generateExportFile($criteria, $rows);
    }
}
