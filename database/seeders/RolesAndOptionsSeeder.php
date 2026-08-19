<?php

namespace Database\Seeders;

use App\Models\FollowupStatus;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Option;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndOptionsSeeder extends Seeder
{
    /**
     * Seed the roles and lookup (option) tables.
     */
    public function run(): void
    {
        foreach ([Role::ADMIN, Role::SALES] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $sources = array_map(fn (string $value) => ['label' => ucwords(strtolower(str_replace('_', ' ', $value)))], LeadSource::all());
        $statuses = array_map(fn (string $value) => ['label' => ucwords(strtolower(str_replace('_', ' ', $value)))], LeadStatus::all());
        $followupStatuses = array_map(fn (string $value) => ['label' => ucwords(strtolower($value))], FollowupStatus::all());
        $userStatuses = array_map(fn (string $value) => ['label' => ucwords(strtolower($value))], [User::STATUS_ACTIVE, User::STATUS_INACTIVE]);

        $this->seedOptions(Option::CATEGORY_USER_STATUS, array_combine([User::STATUS_ACTIVE, User::STATUS_INACTIVE], $userStatuses));
        $this->seedOptions(Option::CATEGORY_LEAD_STATUS, array_combine(LeadStatus::all(), $statuses));
        $this->seedOptions(Option::CATEGORY_LEAD_SOURCE, array_combine(LeadSource::all(), $sources));
        $this->seedOptions(Option::CATEGORY_FOLLOWUP_STATUS, array_combine(FollowupStatus::all(), $followupStatuses));
    }

    /**
     * @param  array<string, array{label: string}>  $values
     */
    protected function seedOptions(string $category, array $values): void
    {
        $sortOrder = 0;

        foreach ($values as $value => $meta) {
            Option::firstOrCreate(
                ['category' => $category, 'value' => $value],
                ['label' => $meta['label'], 'sort_order' => $sortOrder++]
            );
        }
    }
}
