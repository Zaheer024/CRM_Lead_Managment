<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class LeadStatusTransitionTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_valid_happy_path_transitions_are_allowed(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create(['status' => 'NEW']);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'CONTACTED'])->assertOk();
        $this->putJson("/api/leads/{$lead->id}", ['status' => 'FOLLOW_UP'])->assertOk();
        $this->putJson("/api/leads/{$lead->id}", ['status' => 'CONVERTED'])->assertOk();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'CONVERTED']);
    }

    public function test_new_lead_can_be_marked_lost(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create(['status' => 'NEW']);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'LOST'])->assertOk();
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create(['status' => 'CONTACTED']);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'NEW'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid status transition: CONTACTED -> NEW is not allowed.');

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'CONTACTED']);
    }

    public function test_converted_lead_is_protected_from_any_status_change(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create(['status' => 'CONVERTED']);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'NEW'])
            ->assertStatus(409);
    }

    public function test_arbitrary_status_value_is_rejected_at_validation(): void
    {
        $admin = $this->createAdmin();

        Sanctum::actingAs($admin);

        $lead = Lead::factory()->create(['status' => 'NEW']);

        $this->putJson("/api/leads/{$lead->id}", ['status' => 'HELLO'])
            ->assertStatus(422);
    }
}
