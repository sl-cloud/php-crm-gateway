<?php

namespace App\Contracts;

interface SchemaValidatorInterface
{
    /**
     * Validate data against a JSON schema.
     *
     * @param array $data The data to validate
     * @param string $schemaPath The path to the schema file
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $data, string $schemaPath): array;

    /**
     * Check if data is valid against a JSON schema.
     *
     * @param array $data The data to validate
     * @param string $schemaPath The path to the schema file
     * @return bool True if valid, false otherwise
     */
    public function isValid(array $data, string $schemaPath): bool;
}
