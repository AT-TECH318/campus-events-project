<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the three application roles.
     */
    public function run(): void
    {
        foreach (Role::definitions() as $slug => $definition) {
            Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ],
            );
        }
    }
}
