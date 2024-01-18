<?php

namespace Crm\PrintModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\SubscriptionsModule\Repositories\ContentAccessRepository;
use Symfony\Component\Console\Output\OutputInterface;

class ContentAccessSeeder implements ISeeder
{
    private $contentAccessRepository;

    /** @var OutputInterface */
    private $output;

    public function __construct(ContentAccessRepository $contentAccessRepository)
    {
        $this->contentAccessRepository = $contentAccessRepository;
    }

    public function seed(OutputInterface $output)
    {
        $this->output = $output;

        $name = 'print';
        $description = 'Print';
        $class = 'label label-info';
        $sorting = 200;
        $this->seedContentAccess($name, $description, $class, $sorting);
    }

    private function seedContentAccess($name, $description, $class = '', $sorting = 100)
    {
        if (!$this->contentAccessRepository->exists($name)) {
            $this->contentAccessRepository->add(
                $name,
                $description,
                $class,
                $sorting
            );
            $this->output->writeln("  <comment>* content access <info>{$name}</info> created</comment>");
        } else {
            $this->output->writeln("  * content access <info>{$name}</info> exists");
        }
    }
}
