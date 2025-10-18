<?php

namespace Tests\Unit\Services\Validation;

use App\Services\Validation\JsonSchemaValidator;
use Tests\TestCase;

class JsonSchemaValidatorTest extends TestCase
{
    private JsonSchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new JsonSchemaValidator();
    }

    public function test_validates_correct_lead_data(): void
    {
        $data = [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
            'company' => 'Acme Corp',
            'source' => 'website',
            'metadata' => [
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
            ],
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertEmpty($errors);
        $this->assertTrue($this->validator->isValid($data, 'schemas/lead.json'));
    }

    public function test_validates_minimal_lead_data(): void
    {
        $data = [
            'email' => 'minimal@example.com',
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertEmpty($errors);
        $this->assertTrue($this->validator->isValid($data, 'schemas/lead.json'));
    }

    public function test_rejects_invalid_email_format(): void
    {
        $data = [
            'email' => 'invalid-email',
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertNotEmpty($errors);
        $this->assertFalse($this->validator->isValid($data, 'schemas/lead.json'));
        
        $this->assertStringContainsString('email', $errors[0]['property']);
    }

    public function test_rejects_missing_required_email(): void
    {
        $data = [
            'first_name' => 'John',
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertNotEmpty($errors);
        $this->assertFalse($this->validator->isValid($data, 'schemas/lead.json'));
    }

    public function test_rejects_invalid_source_enum(): void
    {
        $data = [
            'email' => 'test@example.com',
            'source' => 'invalid-source',
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertNotEmpty($errors);
        $this->assertFalse($this->validator->isValid($data, 'schemas/lead.json'));
    }

    public function test_accepts_valid_source_enum(): void
    {
        $validSources = ['website', 'referral', 'social', 'email', 'phone', 'advertisement', 'event', 'other'];

        foreach ($validSources as $source) {
            $data = [
                'email' => 'test@example.com',
                'source' => $source,
            ];

            $errors = $this->validator->validate($data, 'schemas/lead.json');

            $this->assertEmpty($errors, "Source '{$source}' should be valid");
        }
    }

    public function test_validates_phone_pattern(): void
    {
        $validPhones = ['+1234567890', '1234567890', '+44123456789'];
        $invalidPhones = ['abc', '123-456-789', '123.456.789'];

        foreach ($validPhones as $phone) {
            $data = [
                'email' => 'test@example.com',
                'phone' => $phone,
            ];

            $errors = $this->validator->validate($data, 'schemas/lead.json');
            $this->assertEmpty($errors, "Phone '{$phone}' should be valid");
        }

        foreach ($invalidPhones as $phone) {
            $data = [
                'email' => 'test@example.com',
                'phone' => $phone,
            ];

            $errors = $this->validator->validate($data, 'schemas/lead.json');
            $this->assertNotEmpty($errors, "Phone '{$phone}' should be invalid");
        }
    }

    public function test_validates_metadata_object(): void
    {
        $data = [
            'email' => 'test@example.com',
            'metadata' => [
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
                'custom_field' => 'custom_value',
            ],
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertEmpty($errors);
    }

    public function test_rejects_non_object_metadata(): void
    {
        $data = [
            'email' => 'test@example.com',
            'metadata' => 'not-an-object',
        ];

        $errors = $this->validator->validate($data, 'schemas/lead.json');

        $this->assertNotEmpty($errors);
    }

    public function test_handles_missing_schema_file(): void
    {
        $data = ['email' => 'test@example.com'];

        $errors = $this->validator->validate($data, 'schemas/nonexistent.json');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Schema file not found', $errors[0]['message']);
    }
}
