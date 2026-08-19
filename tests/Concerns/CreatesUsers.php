<?php

namespace Tests\Concerns;

use App\Models\Option;
use App\Models\Role;
use App\Models\User;

trait CreatesUsers
{
    /**
     * Return the options id for a user status value (e.g. ACTIVE).
     */
    protected function statusId(string $status): ?int
    {
        return Option::query()
            ->where('category', Option::CATEGORY_USER_STATUS)
            ->where('value', $status)
            ->value('id');
    }

    /**
     * Create a user factory row attached to a role.
     */
    protected function createUser(string $role = Role::SALES, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create($attributes);
        $user->roles()->attach($roleModel);

        return $user;
    }

    protected function createAdmin(array $attributes = []): User
    {
        return $this->createUser(Role::ADMIN, $attributes);
    }

    protected function createSales(array $attributes = []): User
    {
        return $this->createUser(Role::SALES, $attributes);
    }
}
