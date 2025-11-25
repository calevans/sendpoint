<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

use EICC\SendPoint\Exception\ValidationException;

class FormValidatorService
{
    public function validate(array $data, array $config): array
    {
        // 0. Honeypot Check
        $honeypotField = $config['honeypot_field'] ?? null;
        if ($honeypotField && !empty($data[$honeypotField])) {
            throw new ValidationException("Spam detected.");
        }

        $cleanedData = [];
        $fieldsConfig = $config['fields'] ?? [];

        // 1. Filter: Only allow fields defined in config
        foreach ($fieldsConfig as $fieldName => $fieldRules) {
            if (array_key_exists($fieldName, $data)) {
                $cleanedData[$fieldName] = $data[$fieldName];
            }
        }

        // 2. Validate: Check rules for each field
        foreach ($fieldsConfig as $fieldName => $fieldRules) {
            $value = $cleanedData[$fieldName] ?? null;
            $isRequired = $fieldRules['required'] ?? false;
            $type = $fieldRules['type'] ?? 'string';

            // Check Required
            if ($isRequired && ($value === null || $value === '')) {
                throw new ValidationException(sprintf("Field '%s' is required.", $fieldName));
            }

            // Skip type check if value is empty and not required
            if ($value === null || $value === '') {
                continue;
            }

            // Check Type
            switch ($type) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new ValidationException(sprintf("Field '%s' must be a valid email address.", $fieldName));
                    }
                    break;
                case 'int':
                case 'integer':
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        throw new ValidationException(sprintf("Field '%s' must be an integer.", $fieldName));
                    }
                    break;
                case 'string':
                default:
                    if (!is_string($value) && !is_numeric($value)) {
                         throw new ValidationException(sprintf("Field '%s' must be a string.", $fieldName));
                    }
                    break;
            }
        }

        return $cleanedData;
    }
}
