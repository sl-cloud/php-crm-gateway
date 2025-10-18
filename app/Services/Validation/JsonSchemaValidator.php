<?php

namespace App\Services\Validation;

use App\Contracts\SchemaValidatorInterface;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Illuminate\Support\Facades\Log;

class JsonSchemaValidator implements SchemaValidatorInterface
{
    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * Validate data against a JSON schema.
     *
     * @param array $data The data to validate
     * @param string $schemaPath The path to the schema file
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $data, string $schemaPath): array
    {
        try {
            // Load schema from file
            $schema = $this->loadSchema($schemaPath);
            
            // Validate data against schema
            $this->validator->validate($data, $schema, Constraint::CHECK_MODE_TYPE_CAST);
            
            if ($this->validator->isValid()) {
                return [];
            }
            
            // Format validation errors
            $errors = [];
            foreach ($this->validator->getErrors() as $error) {
                $errors[] = [
                    'property' => $error['property'],
                    'message' => $error['message'],
                    'constraint' => $error['constraint'],
                ];
            }
            
            return $errors;
            
        } catch (\Exception $e) {
            Log::error('JSON Schema validation error', [
                'error' => $e->getMessage(),
                'schema_path' => $schemaPath,
                'data' => $data,
            ]);
            
            return [
                [
                    'property' => '',
                    'message' => 'Schema validation failed: ' . $e->getMessage(),
                    'constraint' => 'exception',
                ]
            ];
        }
    }

    /**
     * Check if data is valid against a JSON schema.
     *
     * @param array $data The data to validate
     * @param string $schemaPath The path to the schema file
     * @return bool True if valid, false otherwise
     */
    public function isValid(array $data, string $schemaPath): bool
    {
        return empty($this->validate($data, $schemaPath));
    }

    /**
     * Load and decode JSON schema from file.
     *
     * @param string $schemaPath The path to the schema file
     * @return object The decoded schema
     * @throws \Exception If schema file cannot be loaded or decoded
     */
    private function loadSchema(string $schemaPath): object
    {
        $fullPath = resource_path($schemaPath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception("Schema file not found: {$fullPath}");
        }
        
        $schemaContent = file_get_contents($fullPath);
        $schema = json_decode($schemaContent);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in schema file: " . json_last_error_msg());
        }
        
        return $schema;
    }
}
