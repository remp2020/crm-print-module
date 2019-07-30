<?php

namespace Crm\PrintModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\ApplicationModule\SeederManager;
use Crm\ApplicationModule\User\UserDataRegistrator;
use Crm\ApplicationModule\Widget\WidgetManagerInterface;
use Crm\PrintModule\Seeders\ConfigsSeeder;

class PrintModule extends CrmModule
{
    public function registerAdminMenuItems(MenuContainerInterface $menuContainer)
    {
        $printMenu = new MenuItem('Print', '#', 'fa fa-newspaper', 250, false);

        $menuItem1 = new MenuItem(
            $this->translator->translate('print.menu.print_export'),
            ':Print:PrintSubscriptionsAdmin:',
            'fa fa-truck',
            100,
            true
        );

        $menuItem2 = new MenuItem(
            $this->translator->translate('print.menu.ending_subscriptions'),
            ':Print:PrintSubscriptionsAdmin:subscribersEnded',
            'fa fa-calendar',
            200,
            true
        );

        $printMenu->addChild($menuItem1);
        $printMenu->addChild($menuItem2);

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

    public function registerWidgets(WidgetManagerInterface $widgetManager)
    {
        $widgetManager->registerWidget(
            'admin.user.detail.bottom',
            $this->getInstance(\Crm\PrintModule\Components\UserPrintExport::class),
            777
        );
        $widgetManager->registerWidget(
            'admin.user.detail.bottom',
            $this->getInstance(\Crm\PrintModule\Components\UserChangeAddressRequests::class),
            1100
        );
        $widgetManager->registerWidget(
            'admin.users.top',
            $this->getInstance(\Crm\PrintModule\Components\RequestNotification::class),
            1000
        );
        $widgetManager->registerWidget(
            'frontend.payment.success.forms',
            $this->getInstance(\Crm\PrintModule\Components\PaymentSuccessPrintWidget::class)
        );
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'print', 'change-address-request'), \Crm\PrintModule\Api\CreateAddressChangeRequestHandler::class, \Crm\ApiModule\Authorization\BearerTokenAuthorization::class)
        );
    }

    public function registerUserData(UserDataRegistrator $dataRegistrator)
    {
        $dataRegistrator->addUserDataProvider($this->getInstance(\Crm\PrintModule\User\AddressChangeRequestsUserDataProvider::class));
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
    }
}
