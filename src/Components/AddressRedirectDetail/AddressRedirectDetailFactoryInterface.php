<?php

namespace Crm\PrintModule\Components\AddressRedirectDetail;

interface AddressRedirectDetailFactoryInterface
{
    public function create(int $addressRedirectId): AddressRedirectDetail;
}
