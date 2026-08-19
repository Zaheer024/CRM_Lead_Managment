<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class LeadAssignmentTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_inactive_user_cannot_be_assigned(): void
    {
        $admin = $this->createAdmin();
        $inactive = $this->createSales(['status_id' => $this->statusId(User::STATUS_INACTIVE)]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/leads', [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'source' => 'WEBSITE',
            'assigned_to' => $inactive->id,
        ])->assertStatus(422);
    }

    public function test_non_sales_user_cannot_be_assigned(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin(
            ['email' => 'admin2@example.com']
        );

        Sanctum::actingAs($admin);

        $this->postJson('/api/leads', [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'source' => 'WEBSITE',
            'assigned_to' => $otherAdmin->id,
        ])->assertStatus(422);
    }

    public function test_sales_user_can_be_assigned(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales();

        Sanctum::actingAs($admin);

        $this->postJson('/api/leads', [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'source' => 'WEBSITE',
            'assigned_to' => $sales->id,
        ])->assertCreated();
    }

    public function test_sales_user_may_only_assign_leads_to_themselves_on_create(): void
    {
        $sales = $this->createSales();
        $other = $this->createSales(['email' => 'other@example.com']);

        Sanctum::actingAs($sales);

        $this->postJson('/api/leads', [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'source' => 'WEBSITE',
            'assigned_to' => $other->id,
        ])->assertStatus(403);
    }

    public function test_only_admin_can_reassign_a_lead(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales();
        $otherSales = $this->createSales(['email' => 'other@example.com']);

        $lead = Lead::factory()->assignedTo($sales)->create(['status' => 'NEW']);

        Sanctum::actingAs($sales);

        $this->putJson("/api/leads/{$lead->id}", ['assigned_to' => $otherSales->id])
            ->assertStatus(403);

        Sanctum::actingAs($admin);

        $this->putJson("/api/leads/{$lead->id}", ['assigned_to' => $otherSales->id])
            ->assertOk()
            ->assertJsonPath('lead.assigned_to', $otherSales->id);
    }
}
