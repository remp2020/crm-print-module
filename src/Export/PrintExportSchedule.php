<?php

namespace Crm\PrintModule\Export;

use Crm\PrintModule\Models\Export\PrintExportScheduleInterface;

class PrintExportSchedule implements PrintExportScheduleInterface
{
    public function getPrintExportDate(
        \DateTime $exportRunDate,
        ?string $exportType = null,
        ?array $options = null,
    ): ?\DateTime {
        // this schedule handles only daily print
        if ($exportType !== 'print_daily') {
            return null;
        }

        $printExportDate = new \DateTime($exportRunDate->format(\DateTime::ATOM));
        $printExportDate->setTime(8, 0);

        $holidaySchedule = [
            // ...
            // (Example configuration)
            // ///////////////////////////////////////////////////////////
            // Christmas 2024
            // '2024-12-20' => '+7 days', // Friday; export for 27.12. instead of 24.12.
            // '2024-12-23' => '+7 days', // Monday; export for 30.12. instead of 25.12.
            // '2024-12-24' => null, // Christmas Eve; do not export
            // '2024-12-25' => null, // (1st) Christmas Day; do not export
            // '2024-12-26' => null, // (2nd) Christmas Day; do not export
        ];

        // holidays need to be ordered (for delivery) sooner
        $formattedPrintExportDate = $printExportDate->format('Y-m-d');
        if (array_key_exists($formattedPrintExportDate, $holidaySchedule)) {
            if ($holidayExportDate = $holidaySchedule[$formattedPrintExportDate]) {
                return $printExportDate->modify($holidayExportDate);
            }
            $printExportDate = null;
        }
        if ($printExportDate === null) {
            return null;
        }

        switch ($printExportDate->format('N')) {
            case 1:
            case 2:
            case 3:
                return $printExportDate->modify('+2 days');
            case 4:
            case 5:
                return $printExportDate->modify('+4 days');
            case 6:
            case 7:
                return null;
        }
        return null;
    }
}
