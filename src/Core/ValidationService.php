<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class ValidationService
{
    private static function combinePrefixes(string $prefix1, string $prefix2): string
    {
        if ($prefix1 === '' && $prefix2 === '') {
            return '';
        }

        if ($prefix1 === '' && $prefix2 !== '') {
            return $prefix2;
        }

        if ($prefix1 !== '' && $prefix2 === '') {
            return $prefix1;
        }

        return $prefix1 . '.' . $prefix2;
    }

    /**
     * @throws ApiException
     */
    public static function validate(
        string $prefix,
        array $data,
        array $requiredFields,
        array $optionalFields = []
    ): void {
        $validationErrors = [];

        $errorPrefix = '';
        if ($prefix !== '') {
            $errorPrefix = $prefix . '.';
        }

        foreach ($requiredFields as $requiredKey => $requiredFieldType) {
            if (!array_key_exists($requiredKey, $data)) {
                $validationErrors[] = [
                    'field' => $errorPrefix . $requiredKey,
                    'problem' => 'must_exist',
                ];

                continue;
            }

            $result = self::checkType($errorPrefix, $requiredFieldType, $requiredKey, $data[$requiredKey]);
            if ($result !== null) {
                $validationErrors[] = $result;
            }
        }

        foreach ($optionalFields as $optionalKey => $optionalFieldType) {
            if (!array_key_exists($optionalKey, $data) || $data[$optionalKey] === null) {
                continue;
            }

            $result = self::checkType($errorPrefix, $optionalFieldType, $optionalKey, $data[$optionalKey]);
            if ($result !== null) {
                $validationErrors[] = $result;
            }
        }

        if (count($validationErrors) > 0) {
            throw new ApiException(
                ApiException::ERROR_MALFORMED_PAYLOAD,
                $validationErrors,
            );
        }
    }

    /**
     * @param string[] $allowedValues
     * @throws ApiException
     */
    private static function allowedValues(string $prefix, string $value, array $allowedValues): void
    {
        foreach ($allowedValues as $allowedValue) {
            if ($value === $allowedValue) {
                return;
            }
        }

        throw new ApiException(
            ApiException::ERROR_MALFORMED_PAYLOAD,
            [
                'field' => $prefix,
                'problem' => 'not_allowed_value',
            ],
        );
    }

    private static function checkType(string $prefix, string $expectedFieldType, string $key, $value): ?array
    {
        if ($expectedFieldType === 'integer' && !is_numeric($value)) {
            return [
                'field' => $prefix . $key,
                'problem' => 'not_integer'
            ];
        }

        if ($expectedFieldType === 'string' && !is_string($value)) {
            return [
                'field' => $prefix . $key,
                'problem' => 'not_string'
            ];
        }

        if ($expectedFieldType === 'array' && !is_array($value)) {
            return [
                'field' => $prefix . $key,
                'problem' => 'not_array'
            ];
        }

        if ($expectedFieldType === 'object' &&
            (!is_array($value) || array_keys($value) === range(0, count($value) - 1))) {
            return [
                'field' => $prefix . $key,
                'problem' => 'not_object'
            ];
        }

        return null;
    }
}
