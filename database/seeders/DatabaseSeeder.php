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

        $users = [
            'admin@example.com' => [
                'name' => 'Admin User',
                'phone' => '+1 555-0100',
                'status_id' => $activeStatusId,
                'role' => Role::ADMIN,
            ],
            'sales@example.com' => [
                'name' => 'Sales User',
                'phone' => '+1 555-0101',
                'status_id' => $activeStatusId,
                'role' => Role::SALES,
            ],
            'sales2@example.com' => [
                'name' => 'Sales User Two',
                'phone' => '+1 555-0102',
                'status_id' => $activeStatusId,
                'role' => Role::SALES,
            ],
            'inactive@example.com' => [
                'name' => 'Inactive Sales',
                'phone' => '+1 555-0103',
                'status_id' => $inactiveStatusId,
                'role' => Role::SALES,
            ],
        ];

        foreach ($users as $email => $attributes) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $attributes['name'],
                    'phone' => $attributes['phone'],
                    'status_id' => $attributes['status_id'],
                    'password' => 'password',
                ]
            );

            $user->roles()->sync([Role::where('name', $attributes['role'])->value('id')]);
        }
    }
}
