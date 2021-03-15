<?php

namespace Crm\PrintModule\Export;

use Traversable;

interface ViewInterface
{
    public function generateExportFile(ExportCriteria $criteria, Traversable $data);
}
