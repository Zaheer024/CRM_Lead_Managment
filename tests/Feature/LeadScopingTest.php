<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class LeadScopingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_sales_user_only_sees_their_own_leads(): void
    {
        $sales = $this->createSales();
        $other = $this->createSales(['email' => 'other@example.com']);

        Lead::factory()->count(2)->assignedTo($sales)->create();
        Lead::factory()->count(3)->assignedTo($other)->create();

        Sanctum::actingAs($sales);

        $this->getJson('/api/leads')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_admin_sees_all_leads(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales();

        Lead::factory()->count(2)->assignedTo($sales)->create();
        Lead::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/leads')->assertOk()->assertJsonPath('total', 5);
    }

    public function test_sales_user_cannot_view_another_users_lead(): void
    {
        $sales = $this->createSales();
        $other = $this->createSales(['email' => 'other@example.com']);

        $lead = Lead::factory()->assignedTo($other)->create();

        Sanctum::actingAs($sales);

        $this->getJson("/api/leads/{$lead->id}")->assertStatus(403);
    }

    public function test_lead_listing_supports_filters_and_search(): void
    {
        $admin = $this->createAdmin();

        Lead::factory()->create(['email' => 'unique@example.com', 'status' => 'NEW', 'source' => 'WEBSITE']);
        Lead::factory()->create(['email' => 'other@example.com', 'status' => 'CONTACTED', 'source' => 'PHONE']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/leads?status=NEW&source=WEBSITE')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.email', 'unique@example.com');

        $this->getJson('/api/leads?search=other')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_dashboard_returns_counts_by_status_for_admin(): void
    {
        $admin = $this->createAdmin();

        Lead::factory()->status('NEW')->count(2)->create();
        Lead::factory()->status('CONTACTED')->count(3)->create();
        Lead::factory()->status('CONVERTED')->count(1)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJson([
                'total_leads' => 6,
                'new' => 2,
                'contacted' => 3,
                'follow_up' => 0,
                'converted' => 1,
                'lost' => 0,
            ]);
    }

    public function test_dashboard_is_scoped_to_sales_user(): void
    {
        $sales = $this->createSales();
        $other = $this->createSales(['email' => 'other@example.com']);

        Lead::factory()->status('NEW')->count(2)->assignedTo($sales)->create();
        Lead::factory()->status('NEW')->count(5)->assignedTo($other)->create();

        Sanctum::actingAs($sales);

        $this->getJson('/api/dashboard')->assertOk()->assertJson(['total_leads' => 2]);
    }
}
