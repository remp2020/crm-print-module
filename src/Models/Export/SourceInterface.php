<?php

namespace Crm\PrintModule\Models\Export;

interface SourceInterface
{
    public function loadData(ExportCriteria $criteria);
}
