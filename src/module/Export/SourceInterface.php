<?php

namespace Crm\PrintModule\Export;

interface SourceInterface
{
    public function loadData(ExportCriteria $criteria);
}
