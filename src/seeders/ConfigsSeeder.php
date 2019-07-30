<?php

namespace Crm\PrintModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Config\Repository\ConfigCategoriesRepository;
use Crm\ApplicationModule\Config\Repository\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ISeeder;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
    }

    public function seed(OutputInterface $output)
    {
        $category = $this->configCategoriesRepository->loadByName('Print');
        if (!$category) {
            $category = $this->configCategoriesRepository->add('Print', 'fa fa-newspaper', 400);
            $output->writeln('  <comment>* config category <info>Print</info> created</comment>');
        } else {
            $output->writeln(' * config category <info>Print</info> exists');
        }

        $name = 'print_export_issue';
        $config = $this->configsRepository->loadByName($name);
        if (!$config) {
            $this->configBuilder->createNew()
                ->setName($name)
                ->setDisplayName('Názov vydania')
                ->setDescription('Názov vydania v printovom exporte')
                ->setType(ApplicationConfig::TYPE_STRING)
                ->setAutoload(false)
                ->setConfigCategory($category)
                ->setSorting(20)
                ->save();
            $output->writeln("  <comment>* config item <info>$name</info> created</comment>");
        } else {
            $output->writeln("  * config item <info>$name</info> exists");
        }
    }
}
