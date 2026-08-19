<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class LeadCreationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_lead_can_be_created_successfully(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/leads', [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1 555-123-4567',
            'source' => 'WEBSITE',
            'assigned_to' => $sales->id,
            'remarks' => 'Interested in term life.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('lead.email', 'john@example.com')
            ->assertJsonPath('lead.status', 'NEW')
            ->assertJsonPath('lead.assigned_to', $sales->id);

        $this->assertDatabaseHas('leads', [
            'email' => 'john@example.com',
            'status' => 'NEW',
        ]);
    }

    public function test_lead_assigned_to_creator_for_sales_when_not_specified(): void
    {
        $sales = $this->createSales();

        Sanctum::actingAs($sales);

        $response = $this->postJson('/api/leads', [
            'customer_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'source' => 'PHONE',
        ]);

        $response->assertCreated()
            ->assertJsonPath('lead.assigned_to', $sales->id);
    }

    public function test_invalid_status_or_source_is_rejected(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $this->postJson('/api/leads', [
            'customer_name' => 'Sam',
            'email' => 'sam@example.com',
            'source' => 'UNKNOWN_SOURCE',
        ])->assertStatus(422);

        $this->postJson('/api/leads', [
            'customer_name' => 'Sam',
            'email' => 'sam@example.com',
            'source' => 'WEBSITE',
            'status' => 'HELLO',
        ])->assertStatus(422);
    }

    public function test_phone_format_is_validated(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $this->postJson('/api/leads', [
            'customer_name' => 'Sam',
            'email' => 'sam@example.com',
            'source' => 'WEBSITE',
            'phone' => 'not-a-phone-number!!',
        ])->assertStatus(422);
    }

    public function test_duplicate_active_lead_is_rejected(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $payload = [
            'customer_name' => 'John Doe',
            'email' => 'duplicate@example.com',
            'source' => 'WEBSITE',
        ];

        $this->postJson('/api/leads', $payload)->assertCreated();

        $response = $this->postJson('/api/leads', $payload);

        $response->assertStatus(422)
            ->assertJson(['message' => 'A lead with this email already exists and is still active.']);

        $this->assertDatabaseCount('leads', 1);
    }

    public function test_new_lead_allowed_after_previous_is_converted(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create([
            'email' => 'repeat@example.com',
            'status' => 'NEW',
        ]);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'CONTACTED'])->assertOk();
        $this->putJson("/api/leads/{$lead->id}", ['status' => 'FOLLOW_UP'])->assertOk();
        $this->putJson("/api/leads/{$lead->id}", ['status' => 'CONVERTED'])->assertOk();

        $this->postJson('/api/leads', [
            'customer_name' => 'New Customer',
            'email' => 'repeat@example.com',
            'source' => 'CAMPAIGN',
        ])->assertCreated();

        $this->assertDatabaseCount('leads', 2);
    }
}
