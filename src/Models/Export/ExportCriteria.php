<?php

namespace Crm\PrintModule\Models\Export;

use DateTime;

class ExportCriteria
{
    /**
     * @param string $key Issue type (eg. print_daily, print_friday, monthly_special).
     * @param DateTime $exportAt Date and time of export (stored to print_subscriptions.exported_at).
     * @param DateTime $exportTo Date of issue publication (stored to print_subscriptions.export_date).
     * @param bool $backIssues If set to true, only new subscriptions (for back issues) are generated
     *                         and exported (eg. running export week after first batch).
     * @param ?array $allowedCountries Whitelist countries (ISO code).
     *                                 If set to null, all countries are allowed in export.
     * @param ?\Closure $changeStatusCallback This callback is called after print_subscriptions are stored
     *                                        to update status of these entries. If no callback is set, default
     *                                        PrintSubscriptionsRepository::setPrintExportStatus() is used.
     */
    public function __construct(
        private string $key,
        private DateTime $exportAt,
        private DateTime $exportTo,
        private bool $backIssues = false,
        private ?array $allowedCountries = null,
        private ?\Closure $changeStatusCallback = null,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getExportAt(): DateTime
    {
        return $this->exportAt;
    }

    public function getExportTo(): DateTime
    {
        return $this->exportTo;
    }

    public function getBackIssues(): bool
    {
        return $this->backIssues;
    }

    public function getAllowedCountries(): ?array
    {
        return $this->allowedCountries;
    }

    public function shouldDeliverToCountry(string $isoCode): bool
    {
        // nothing set; export everything
        if ($this->allowedCountries === null) {
            return true;
        }

        return in_array($isoCode, $this->allowedCountries, true);
    }

    public function hasChangeStatusCallback(): bool
    {
        return $this->changeStatusCallback !== null;
    }

    public function callChangeStatusCallback(
        string $exportType,
        DateTime $exportPrintDate,
        DateTime $exportRunDate,
    ): bool {
        if ($this->changeStatusCallback === null) {
            return false;
        }
        return ($this->changeStatusCallback)($exportType, $exportPrintDate, $exportRunDate);
    }
}
