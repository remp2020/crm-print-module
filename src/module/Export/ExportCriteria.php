<?php

namespace Crm\PrintModule\Export;

use DateTime;

class ExportCriteria
{
    private $key;

    private $exportAt;

    private $exportTo;

    public function __construct($key, DateTime $exportAt, DateTime $exportTo)
    {
        $this->key = $key;
        $this->exportAt = $exportAt;
        $this->exportTo = $exportTo;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getExportAt()
    {
        return $this->exportAt;
    }

    public function getExportTo()
    {
        return $this->exportTo;
    }
}
