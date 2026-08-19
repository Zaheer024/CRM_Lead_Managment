<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadFollowup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_followup_can_be_created_for_active_lead(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales();

        $lead = Lead::factory()->assignedTo($sales)->create(['status' => 'NEW']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/leads/{$lead->id}/followups", [
            'followup_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Call back about the quote.',
        ])->assertCreated()
            ->assertJsonPath('followup.status', 'PENDING');

        $this->assertDatabaseHas('lead_followups', ['lead_id' => $lead->id]);
    }

    public function test_followup_cannot_be_created_for_converted_lead(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'CONVERTED']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/leads/{$lead->id}/followups", [
            'followup_date' => now()->addDays(3)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_followup_cannot_be_created_for_lost_lead(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'LOST']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/leads/{$lead->id}/followups", [
            'followup_date' => now()->addDays(3)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_followup_date_cannot_be_in_the_past(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'NEW']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/leads/{$lead->id}/followups", [
            'followup_date' => now()->subDay()->toDateString(),
        ])->assertStatus(422);
    }

    public function test_followup_status_can_be_updated(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'NEW']);
        $followup = LeadFollowup::factory()->create([
            'lead_id' => $lead->id,
            'status' => 'PENDING',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/followups/{$followup->id}", [
            'status' => 'COMPLETED',
            'notes' => 'Completed the call.',
        ])->assertOk()
            ->assertJsonPath('followup.status', 'COMPLETED');
    }

    public function test_followups_can_be_listed_for_a_lead(): void
    {
        $admin = $this->createAdmin();

        $lead = Lead::factory()->create(['status' => 'NEW']);
        LeadFollowup::factory()->count(2)->create(['lead_id' => $lead->id]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/leads/{$lead->id}/followups")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
