<?php

namespace Crm\PrintModule\Export;

use Crm\ApplicationModule\ExcelFactory;
use League\Flysystem\MountManager;
use Nette\Utils\Json;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class DailyExportView implements ViewInterface
{
    private $excelFactory;

    private $mountManager;

    public function __construct(
        ExcelFactory $excelFactory,
        MountManager $mountManager
    ) {
        $this->excelFactory = $excelFactory;
        $this->mountManager = $mountManager;
    }

    public function generateExportFile(ExportCriteria $criteria, \Traversable $data)
    {
        $excel = $this->excelFactory->createExcel('Print Subscriptions');

        $excel->getActiveSheet()->setTitle('Export');

        $exportDate = $criteria->getExportTo();

        if ($exportDate) {
            $excel->getActiveSheet()->setTitle($exportDate->format('Y-m-d'));

            $rows = [];

            $rows[] = [
                'subscription_id',
                'user_id',
                'address_id',
                'print_subscription_id',
                'name',
                'street',
                'number',
                'city',
                'zip',
                'delivery_date',
            ];

            foreach ($data as $printSubscription) {
                $name = trim($printSubscription->first_name . ' ' . $printSubscription->last_name);
                if ($printSubscription->institution_name) {
                    $name = trim($name ? "{$printSubscription->institution_name}, $name" : $printSubscription->institution_name);
                }
                if (!$name) {
                    $name = $printSubscription->email;
                }

                $meta = Json::decode($printSubscription->meta);
                if (!$meta) {
                    if ($printSubscription->status === 'removed') {
                        continue;
                    }
                    throw new \Exception('metadata missing in daily export view for print subscription: ' . $printSubscription->id);
                }

                $rows[] = [
                    $printSubscription->subscription_id, // subscription_id
                    $printSubscription->user->id, // user_id
                    $meta->address_change_request_id ?? null, // address_id
                    $printSubscription->id, // ext_id
                    str_replace("\n", ", ", $name), // name
                    $printSubscription->address, // street
                    $printSubscription->number, // number
                    $printSubscription->city, // city
                    $printSubscription->zip, // zip
                    $printSubscription->export_date->format('Y-m-d'), // delivery_date
                ];
            }

            $excel->getActiveSheet()
                ->fromArray($rows);

            $csv = new Csv($excel);
            $csv->setDelimiter(';');

            $fileName = $criteria->getKey() . '-' . $exportDate->format('Y-m-d') . '.csv';
            $filePath = $this->mountManager->getAdapter(FileSystem::DEFAULT_BUCKET_NAME . '://')->applyPathPrefix($fileName);

            $csv->save($filePath);
            return $filePath;
        }

        return null;
    }
}
