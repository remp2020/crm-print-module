<?php

namespace Crm\PrintModule\Models;

class ClaimType
{
    private const INCOMPLETE_DELIVERY = 'CD';
    private const WRONG_NEWSPAPER = 'DJIN';
    private const DELIVERY_INFORMATION = 'INFO';
    private const INCORRECT_COUNT = 'KS';
    private const NOT_DELIVERED = 'ND';
    private const NOT_DELIVERED_DONT_DELIVER = 'NDNP';
    private const LATE_DELIVERY = 'PD';
    private const DAMAGED = 'POS';
    private const AFTER_END = 'PUK';

    public static function pairs()
    {
        return [
            self::NOT_DELIVERED => 'print.model.claim_type.not_delivered',
            self::INCOMPLETE_DELIVERY => 'print.model.claim_type.incomplete_delivery',
            self::WRONG_NEWSPAPER => 'print.model.claim_type.wrong_newspaper',
            self::DELIVERY_INFORMATION => 'print.model.claim_type.delivery_information',
            self::INCORRECT_COUNT => 'print.model.claim_type.incorrect_count',
            self::NOT_DELIVERED_DONT_DELIVER => 'print.model.claim_type.not_delivered_dont_deliver',
            self::LATE_DELIVERY => 'print.model.claim_type.late_delivery',
            self::DAMAGED => 'print.model.claim_type.damaged',
            self::AFTER_END => 'print.model.claim_type.after_end',
        ];
    }
}
