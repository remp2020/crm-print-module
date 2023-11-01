<?php

namespace Crm\PrintModule\Models\Export;

class FilePatternConfig
{
    private array $patterns = [];

    public function setFilePattern(string $exportKey, string $pattern)
    {
        $this->patterns[$exportKey] = $pattern;
    }

    public function evaluate(string $exportKey, \DateTime $date): ?string
    {
        if (!isset($this->patterns[$exportKey])) {
            return null;
        }

        $pattern = $this->patterns[$exportKey];
        $matches = [];

        preg_match('/(#([^#]*)#)/', $pattern, $matches);

        if (!isset($matches[2])) {
            return null;
        }

        return str_replace($matches[1], $date->format($matches[2]), $pattern);
    }
}
