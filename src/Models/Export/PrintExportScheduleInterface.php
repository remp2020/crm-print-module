<?php

declare(strict_types=1);

namespace Crm\PrintModule\Models\Export;

interface PrintExportScheduleInterface
{
    public function getPrintExportDate(
        \DateTime $exportRunDate,
        ?string $exportType = null,
        ?array $options = null,
    ): ?\DateTime;
}
