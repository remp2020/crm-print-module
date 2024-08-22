<?php

declare(strict_types=1);

namespace Crm\PrintModule\Commands;

use Crm\PrintModule\Export\DailyExportDataSource;
use Crm\PrintModule\Export\DailyExportView;
use Crm\PrintModule\Models\Export\ExportCriteria;
use Crm\PrintModule\Models\Export\ExportEngine;
use Crm\PrintModule\Models\Export\PrintExportScheduleInterface;
use Crm\PrintModule\Repositories\PrintSubscriptionsRepository;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDailyCommand extends Command
{
    public function __construct(
        private DailyExportDataSource $dataSource,
        private DailyExportView $view,
        private ExportEngine $exportEngine,
        private PrintSubscriptionsRepository $printSubscriptionsRepository,
        private PrintExportScheduleInterface $printExportSchedule,
    ) {
        parent::__construct();
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
        ini_set('memory_limit', '512M');
        if ($inputDate = $input->getArgument('date')) {
            $exportRunDate = DateTime::from($inputDate);
            $exportRunDate->setTime(8, 0);
        } else {
            $exportRunDate = new \DateTime();
        }

        $printExportDate = $this->printExportSchedule->getPrintExportDate($exportRunDate, 'print_daily');
        if ($printExportDate === null) {
            $output->writeln("Not printing day");
            return Command::SUCCESS;
        }

        $criteria = new ExportCriteria('print_daily', $exportRunDate, $printExportDate);

        $output->writeln("Exporting daily print subscribers for {$printExportDate->format('Y-m-d H:i:s')}");

        $this->printSubscriptionsRepository->deleteList($criteria->getKey(), $printExportDate->format('Y-m-d'));
        $storedCsv = $this->exportEngine->run($criteria, $this->dataSource, $this->view);

        return Command::SUCCESS;
    }
}
