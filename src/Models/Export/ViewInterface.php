<?php

namespace Crm\PrintModule\Models\Export;

use Traversable;

interface ViewInterface
{
    public function generateExportFile(ExportCriteria $criteria, Traversable $data);
}
