<?php

namespace Crm\PrintModule;

use Crm\ApplicationModule\CallbackManagerInterface;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\ApplicationModule\SeederManager;
use Crm\ApplicationModule\User\UserDataRegistrator;
use Crm\ApplicationModule\Widget\LazyWidgetManagerInterface;
use Crm\PrintModule\Commands\ExportDailyCommand;
use Crm\PrintModule\Components\EnterAddressWidget;
use Crm\PrintModule\Components\PaymentSuccessPrintWidget;
use Crm\PrintModule\Components\RequestNotification;
use Crm\PrintModule\Components\UserChangeAddressRequests;
use Crm\PrintModule\Components\UserPrintExport;
use Crm\PrintModule\Repository\PrintSubscriptionsRepository;
use Crm\PrintModule\Seeders\AddressTypesSeeder;
use Crm\PrintModule\Seeders\ConfigsSeeder;
use Crm\PrintModule\Seeders\ContentAccessSeeder;
use Crm\PrintModule\User\AddressChangeRequestsUserDataProvider;
use Crm\PrintModule\User\PrintAddressesUserDataProvider;
use Nette\DI\Container;

class PrintModule extends CrmModule
{
    public function registerAdminMenuItems(MenuContainerInterface $menuContainer)
    {
        $printMenu = new MenuItem('Print', '#print', 'fa fa-newspaper', 250, false);

        $menuItem1 = new MenuItem(
            $this->translator->translate('print.menu.print_export'),
            ':Print:PrintSubscriptionsAdmin:',
            'fa fa-truck',
            100,
            true
        );

        $printMenu->addChild($menuItem1);

        $menuContainer->attachMenuItem($printMenu);

        // dashboard menu item

        $menuItem = new MenuItem(
            $this->translator->translate('print.menu.stats'),
            ':Print:Dashboard:default',
            'fa fa-newspaper',
            450
        );
        $menuContainer->attachMenuItemToForeignModule('#dashboard', $printMenu, $menuItem);
    }

    public function registerFrontendMenuItems(MenuContainerInterface $menuContainer)
    {
        $menuItem = new MenuItem(
            $this->translator->translate('print.menu.delivery_address'),
            ':Print:ChangeUserAddressRequest:changeAddressRequest',
            '',
            500,
            true
        );
        $menuContainer->attachMenuItem($menuItem);
    }

    public function registerLazyWidgets(LazyWidgetManagerInterface $widgetManager)
    {
        $widgetManager->registerWidget(
            'admin.user.detail.bottom',
            UserPrintExport::class,
            777
        );
        $widgetManager->registerWidget(
            'admin.user.detail.bottom',
            UserChangeAddressRequests::class,
            1100
        );
        $widgetManager->registerWidget(
            'admin.users.top',
            RequestNotification::class,
            1000
        );
        $widgetManager->registerWidget(
            'payment.address',
            PaymentSuccessPrintWidget::class
        );

        $widgetManager->registerWidget(
            'frontend.layout.top',
            EnterAddressWidget::class,
            100
        );
    }

    public function registerUserData(UserDataRegistrator $dataRegistrator)
    {
        $dataRegistrator->addUserDataProvider($this->getInstance(AddressChangeRequestsUserDataProvider::class));
        $dataRegistrator->addUserDataProvider($this->getInstance(PrintAddressesUserDataProvider::class));
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
        $seederManager->addSeeder($this->getInstance(AddressTypesSeeder::class));
        $seederManager->addSeeder($this->getInstance(ContentAccessSeeder::class));
    }

    public function registerCommands(CommandsContainerInterface $commandsContainer)
    {
        $commandsContainer->registerCommand($this->getInstance(ExportDailyCommand::class));
    }

    public function registerCleanupFunction(CallbackManagerInterface $cleanUpManager)
    {
        $cleanUpManager->add(PrintSubscriptionsRepository::class, function (Container $container) {
            /** @var PrintSubscriptionsRepository $printSubscriptionsRepository */
            $printSubscriptionsRepository = $container->getByType(PrintSubscriptionsRepository::class);
            $printSubscriptionsRepository->removeUnusedPrintAddresses();
        });
    }
}
