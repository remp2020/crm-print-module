<?php

namespace Crm\PrintModule\Events;

use Crm\UsersModule\Events\NewAddressEvent;
use Crm\UsersModule\Repositories\AddressesRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class NewPrintAddressEventHandler extends AbstractListener
{
    public function __construct(
        private readonly AddressesRepository $addressesRepository,
    ) {
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof NewAddressEvent)) {
            throw new \Exception('invalid type of event received: ' . get_class($event));
        }

        $address = $event->getAddress();
        if ($address->type !== 'print') {
            return;
        }

        $defaultAddress = $this->addressesRepository->address($address->user, $address->type, true);
        if (!$defaultAddress) {
            $this->addressesRepository->update($address, ['is_default' => true]);
        }
    }
}
