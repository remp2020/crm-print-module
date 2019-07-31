<?php

namespace Crm\PrintModule\Commands;

use Crm\PrintModule\Export\DailyExportDataSource;
use Crm\PrintModule\Export\DailyExportView;
use Crm\PrintModule\Export\ExportCriteria;
use Crm\PrintModule\Export\ExportEngine;
use Crm\PrintModule\Repository\PrintSubscriptionsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Crm\UsersModule\Repository\AddressesMetaRepository;
use Crm\UsersModule\Repository\AddressesRepository;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDailyCommand extends Command
{
    private $dataSource;

    private $view;

    private $addressesRepository;

    private $addressesMetaRepository;

    private $printSubscriptionsRepository;

    private $subscriptionsRepository;

    private $exportEngine;

    public function __construct(
        DailyExportDataSource $dataSource,
        DailyExportView $view,
        AddressesRepository $addressesRepository,
        AddressesMetaRepository $addressesMetaRepository,
        PrintSubscriptionsRepository $printSubscriptionsRepository,
        SubscriptionsRepository $subscriptionsRepository,
        ExportEngine $exportEngine
    ) {
        parent::__construct();
        $this->dataSource = $dataSource;
        $this->view = $view;
        $this->addressesRepository = $addressesRepository;
        $this->addressesMetaRepository = $addressesMetaRepository;
        $this->printSubscriptionsRepository = $printSubscriptionsRepository;
        $this->subscriptionsRepository = $subscriptionsRepository;
        $this->exportEngine = $exportEngine;
    }

    public function configure()
    {
        $this->setName('print:export_daily')
            ->setDescription('Generates csv file containing list of current print subscribers and their addresses')
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date to create print subscriptions list'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($inputDate = $input->getArgument('date')) {
            $exportRunDate = DateTime::from($inputDate);
            $exportRunDate->setTime(8, 0);
        } else {
            $exportRunDate = new \DateTime();
        }

        $printExportDate = new DateTime($exportRunDate->format(DateTime::ATOM));
        $printExportDate->setTime(8, 0);

        $holidaySchedule = [
        ];

        // holidays need to be ordered sooner
        $formattedPrintExportDate = $printExportDate->format('Y-m-d');
        if (array_key_exists($formattedPrintExportDate, $holidaySchedule)) {
            if ($holidayExportDate = $holidaySchedule[$formattedPrintExportDate]) {
                $printExportDate->modify($holidayExportDate);
            } else {
                $printExportDate = null;
            }
        } else {
            switch ($printExportDate->format('N')) {
                case 1:
                case 2:
                case 3:
                    $printExportDate->modify('+2 days');
                    break;
                case 4:
                case 5:
                    $printExportDate->modify('+4 days');
                    break;
                case 6:
                case 7:
                    $printExportDate = null;
                    break;
            }
        }

        if ($printExportDate === null) {
            $output->writeln("Not printing day");
            return;
        }

        $criteria = new ExportCriteria('print_daily', $exportRunDate, $printExportDate);

        $output->writeln("Exporting daily print subscribers for {$printExportDate->format('Y-m-d H:i:s')}");

        $this->printSubscriptionsRepository->deleteList($criteria->getKey(), $printExportDate->format('Y-m-d'));
        $storedCsv = $this->exportEngine->run($criteria, $this->dataSource, $this->view);
    }
}
