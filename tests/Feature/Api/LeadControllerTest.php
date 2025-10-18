<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_create_lead_with_valid_data(): void
    {
        $leadData = [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
            'company' => 'Acme Corp',
            'source' => 'website',
            'metadata' => [
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'summer-sale',
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'lead_id',
                    'correlation_id',
                ],
            ]);

        $this->assertDatabaseHas('leads', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tenant_id' => $this->user->id,
        ]);
    }

    public function test_can_create_lead_with_minimal_data(): void
    {
        $leadData = [
            'email' => 'minimal@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('leads', [
            'email' => 'minimal@example.com',
            'tenant_id' => $this->user->id,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $leadData = [
            'email' => 'test@example.com',
        ];

        $response = $this->postJson('/api/leads', $leadData);

        $response->assertStatus(401);
    }

    public function test_validates_required_email(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validates_email_format(): void
    {
        $leadData = [
            'email' => 'invalid-email',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validates_source_enum(): void
    {
        $leadData = [
            'email' => 'test@example.com',
            'source' => 'invalid-source',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source']);
    }

    public function test_generates_correlation_id_if_not_provided(): void
    {
        $leadData = [
            'email' => 'test@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(201);

        $lead = Lead::where('email', 'test@example.com')->first();
        $this->assertNotNull($lead->correlation_id);
        $this->assertIsString($lead->correlation_id);
    }

    public function test_uses_provided_correlation_id(): void
    {
        $correlationId = 'test-correlation-id-123';
        $leadData = [
            'email' => 'test@example.com',
            'correlation_id' => $correlationId,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(201);

        $lead = Lead::where('email', 'test@example.com')->first();
        $this->assertEquals($correlationId, $lead->correlation_id);
    }

    public function test_sets_tenant_id_from_authenticated_user(): void
    {
        $leadData = [
            'email' => 'test@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/leads', $leadData);

        $response->assertStatus(201);

        $lead = Lead::where('email', 'test@example.com')->first();
        $this->assertEquals($this->user->id, $lead->tenant_id);
    }
}
