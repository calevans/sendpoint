<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class FormConfigService
{
    public function __construct(
        private string $configDirectory
    ) {}

    public function loadConfig(string $formId): ?array
    {
        // Sanitize formId to prevent directory traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $formId)) {
            return null;
        }

        $filePath = $this->configDirectory . '/' . $formId . '.yml';

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            return Yaml::parseFile($filePath);
        } catch (ParseException $e) {
            // In a real app we might log this, but for now returning null is sufficient to trigger 400
            return null;
        }
    }
}
