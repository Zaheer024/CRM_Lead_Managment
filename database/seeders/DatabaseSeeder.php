<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndOptionsSeeder::class);

        $activeStatusId = Option::query()
            ->where('category', Option::CATEGORY_USER_STATUS)
            ->where('value', User::STATUS_ACTIVE)
            ->value('id');
        $inactiveStatusId = Option::query()
            ->where('category', Option::CATEGORY_USER_STATUS)
            ->where('value', User::STATUS_INACTIVE)
            ->value('id');

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '+1 555-0100',
            'status_id' => $activeStatusId,
        ]);
        $admin->roles()->attach(Role::where('name', Role::ADMIN)->value('id'));

        $sales = User::factory()->create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'phone' => '+1 555-0101',
            'status_id' => $activeStatusId,
        ]);
        $sales->roles()->attach(Role::where('name', Role::SALES)->value('id'));

        // A second sales user used to demonstrate role based scoping.
        $salesTwo = User::factory()->create([
            'name' => 'Sales User Two',
            'email' => 'sales2@example.com',
            'phone' => '+1 555-0102',
            'status_id' => $activeStatusId,
        ]);
        $salesTwo->roles()->attach(Role::where('name', Role::SALES)->value('id'));

        // An inactive sales user used to demonstrate the assignment rule.
        $inactive = User::factory()->create([
            'name' => 'Inactive Sales',
            'email' => 'inactive@example.com',
            'phone' => '+1 555-0103',
            'status_id' => $inactiveStatusId,
        ]);
        $inactive->roles()->attach(Role::where('name', Role::SALES)->value('id'));
    }
}
