# API Documentation

## Overview

The PHP CRM Gateway API provides endpoints for lead management with AWS SQS integration and comprehensive validation.

**Base URL**: `http://localhost:8080/api`

**Authentication**: Bearer Token (Laravel Sanctum)

## Authentication

### Generate API Token

**POST** `/auth/token`

Generate a new API token for authentication.

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token generated successfully",
  "data": {
    "token": "1|abcdef123456...",
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": "Admin User"
    }
  }
}
```

**Error Responses:**
- `422 Unprocessable Entity`: Validation errors
- `401 Unauthorized`: Invalid credentials

### Revoke API Token

**POST** `/auth/logout`

Revoke the current API token.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token revoked successfully"
}
```

## Lead Management

### Create Lead

**POST** `/leads`

Create a new lead and publish LeadCreated event to SQS.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "company": "Acme Corp",
  "source": "website",
  "metadata": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "summer-sale",
    "utm_content": "banner-ad",
    "utm_term": "crm software",
    "referrer": "https://google.com",
    "user_agent": "Mozilla/5.0...",
    "ip_address": "192.168.1.1"
  }
}
```

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | Lead's email address (valid email format) |
| `first_name` | string | No | Lead's first name (max 255 chars) |
| `last_name` | string | No | Lead's last name (max 255 chars) |
| `phone` | string | No | Lead's phone number (valid phone format) |
| `company` | string | No | Lead's company name (max 255 chars) |
| `source` | string | No | Lead source (enum: website, referral, social, email, phone, advertisement, event, other) |
| `metadata` | object | No | Additional metadata (UTM parameters, tracking info) |

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Lead created successfully",
  "data": {
    "lead_id": 1,
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid token
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

**Validation Errors Example:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "source": ["The selected source is invalid."]
  }
}
```

## API Information

### Get API Info

**GET** `/`

Get basic API information.

**Response (200 OK):**
```json
{
  "name": "PHP CRM Gateway API",
  "version": "1.0.0",
  "description": "A Laravel API gateway for lead management with SQS integration"
}
```

## Data Models

### Lead Model

```json
{
  "id": 1,
  "tenant_id": "1",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890",
  "company": "Acme Corp",
  "source": "website",
  "metadata": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "summer-sale"
  },
  "created_at": "2023-01-01T00:00:00Z",
  "updated_at": "2023-01-01T00:00:00Z"
}
```

### User Model

```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@example.com",
  "email_verified_at": "2023-01-01T00:00:00Z",
  "created_at": "2023-01-01T00:00:00Z",
  "updated_at": "2023-01-01T00:00:00Z"
}
```

## SQS Integration

### LeadCreated Event

When a lead is successfully created, a LeadCreated event is published to the SQS leads queue.

**Message Body:**
```json
{
  "event_type": "LeadCreated",
  "tenant_id": "1",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "lead_id": 1,
  "lead_data": {
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "company": "Acme Corp",
    "source": "website",
    "metadata": {
      "utm_source": "google",
      "utm_medium": "cpc",
      "utm_campaign": "summer-sale"
    }
  },
  "timestamp": "2023-01-01T00:00:00Z"
}
```

**Message Attributes:**
- `EventType`: "LeadCreated"
- `CorrelationId`: Request tracking ID
- `TenantId`: User/tenant identifier
- `Timestamp`: ISO 8601 timestamp

### Log Events

When logging is configured for remote mode, log events are published to the SQS log events queue.

**Message Body:**
```json
{
  "level": "info",
  "message": "Lead created successfully",
  "context": {
    "lead_id": 1,
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "tenant_id": "1",
    "email": "john@example.com"
  },
  "timestamp": "2023-01-01T00:00:00Z",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "tenant_id": "1"
}
```

## Error Handling

### Standard Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 401 | Unauthorized - Authentication required |
| 422 | Unprocessable Entity - Validation errors |
| 500 | Internal Server Error - Server error |

### Common Error Scenarios

1. **Authentication Errors**
   - Missing Authorization header
   - Invalid or expired token
   - Malformed token format

2. **Validation Errors**
   - Missing required fields
   - Invalid field formats
   - JSON Schema validation failures

3. **Server Errors**
   - Database connection issues
   - SQS publishing failures
   - Internal application errors

## Rate Limiting

Currently, no rate limiting is implemented. Future versions will include:
- Per-user rate limiting
- Per-endpoint rate limiting
- Redis-based rate limiting

## CORS Configuration

The API supports CORS for cross-origin requests. Configured domains:
- `localhost:8080`
- `127.0.0.1:8080`

## SDK Examples

### cURL Examples

**Generate Token:**
```bash
curl -X POST http://localhost:8080/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**Create Lead:**
```bash
curl -X POST http://localhost:8080/api/leads \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "source": "website"
  }'
```

### JavaScript Examples

**Using Fetch API:**
```javascript
// Generate token
const tokenResponse = await fetch('http://localhost:8080/api/auth/token', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'admin@example.com',
    password: 'password'
  })
});

const tokenData = await tokenResponse.json();
const token = tokenData.data.token;

// Create lead
const leadResponse = await fetch('http://localhost:8080/api/leads', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'john@example.com',
    first_name: 'John',
    last_name: 'Doe',
    source: 'website'
  })
});

const leadData = await leadResponse.json();
console.log(leadData);
```

### PHP Examples

**Using Guzzle HTTP:**
```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'http://localhost:8080/api/']);

// Generate token
$tokenResponse = $client->post('auth/token', [
    'json' => [
        'email' => 'test@example.com',
        'password' => 'password'
    ]
]);

$tokenData = json_decode($tokenResponse->getBody(), true);
$token = $tokenData['data']['token'];

// Create lead
$leadResponse = $client->post('leads', [
    'headers' => [
        'Authorization' => "Bearer {$token}",
    ],
    'json' => [
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'source' => 'website'
    ]
]);

$leadData = json_decode($leadResponse->getBody(), true);
```

## Testing

### Test Users

Default test users are created during database seeding:

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Admin |
| test@example.com | password | Test User |

### Test Data

Use the provided test users or create new ones via the UserSeeder.

### Integration Testing

The API includes comprehensive tests:
- Feature tests for end-to-end API testing
- Unit tests for individual services
- Integration tests with LocalStack SQS

Run tests with:
```bash
php artisan test
```

## Changelog

### Version 1.0.0
- Initial release
- Lead creation with SQS integration
- JSON Schema validation
- Switchable logging system
- Laravel Sanctum authentication
- Comprehensive test coverage
- OpenAPI documentation
