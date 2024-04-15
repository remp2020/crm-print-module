<?php

namespace Crm\PrintModule\Models\Export;

class PrintClaimsExport
{
    private const EXPORT_HEADER = [
        'print_subscription_id',
        'description',
        'claimant',
        'claimant_contact',
        'claim_created_at',
        'claim_closed_at',
        'claim_updated_at',
        'print_subscription_type',
        'subscription_id',
        'user_id',
        'exported_at',
        'export_date',
        'first_name',
        'last_name',
        'address',
        'zip',
        'city',
        'phone_number',
        'country',
        'email',
        'status',
        'institution_name'
    ];

    public function getExport($printClaims): array
    {
        $page = 0;
        $step = 1000;
        $tableData = [];
        while (true) {
            $rows = (clone $printClaims)->limit($step, $page*$step);
            $fetchedRows = 0;

            foreach ($rows as $row) {
                $fetchedRows++;

                $tableData[] = [
                    $row->print_subscription_id,
                    $row->description,
                    $row->claimant,
                    $row->claimant_contact,
                    $row->created_at,
                    $row->closed_at,
                    $row->updated_at,
                    $row->print_subscription->type,
                    $row->print_subscription->subscription_id,
                    $row->print_subscription->user_id,
                    $row->print_subscription->exported_at,
                    $row->print_subscription->export_date,
                    $row->print_subscription->first_name,
                    $row->print_subscription->last_name,
                    $row->print_subscription->address . ' ' . $row->print_subscription->number,
                    $row->print_subscription->zip,
                    $row->print_subscription->city,
                    $row->print_subscription->phone_number,
                    $row->print_subscription->country->name,
                    $row->print_subscription->email,
                    $row->print_subscription->status,
                    $row->print_subscription->institution_name,
                ];
            }

            if ($fetchedRows < $step) {
                break;
            }
        }

        return [self::EXPORT_HEADER, ...$tableData];
    }
}
