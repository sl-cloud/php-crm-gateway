<?php

namespace App\DTOs;

class LeadDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $correlationId,
        public readonly string $email,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phone = null,
        public readonly ?string $company = null,
        public readonly ?string $source = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create LeadDTO from array data.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            correlationId: $data['correlation_id'],
            email: $data['email'],
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null,
            company: $data['company'] ?? null,
            source: $data['source'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert LeadDTO to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'company' => $this->company,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert LeadDTO to array for database insertion.
     *
     * @return array
     */
    public function toDatabaseArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'company' => $this->company,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert LeadDTO to array for SQS message.
     *
     * @return array
     */
    public function toSqsMessage(): array
    {
        return [
            'event_type' => 'LeadCreated',
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
            'lead_data' => [
                'email' => $this->email,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'phone' => $this->phone,
                'company' => $this->company,
                'source' => $this->source,
                'metadata' => $this->metadata,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
