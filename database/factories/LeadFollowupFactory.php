<?php

namespace Database\Factories;

use App\Models\FollowupStatus;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadFollowup>
 */
class LeadFollowupFactory extends Factory
{
    protected $model = LeadFollowup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'followup_date' => now()->addDays(random_int(1, 14))->toDateString(),
            'notes' => fake()->sentence(),
            'status' => fake()->randomElement(FollowupStatus::all()),
            'created_by' => User::factory(),
        ];
    }
}
