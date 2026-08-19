<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ConvertedLeadProtectionTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_converted_lead_cannot_be_edited(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'CONVERTED']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/leads/{$lead->id}", ['customer_name' => 'Renamed'])
            ->assertStatus(409);

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'customer_name' => $lead->customer_name]);
    }

    public function test_converted_lead_cannot_be_deleted(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'CONVERTED']);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/leads/{$lead->id}")->assertStatus(409);

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_sales_user_cannot_delete_any_lead(): void
    {
        $sales = $this->createSales();

        $lead = Lead::factory()->assignedTo($sales)->create(['status' => 'NEW']);

        Sanctum::actingAs($sales);

        $this->deleteJson("/api/leads/{$lead->id}")->assertStatus(403);

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_delete_a_non_converted_lead(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'LOST']);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/leads/{$lead->id}")->assertOk();

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }
}
