<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;

trait AuthenticatesWithToken
{
    protected function seedRoles(): void
    {
        foreach (Role::definitions() as $slug => $definition) {
            Role::query()->firstOrCreate(
                ['slug' => $slug],
                $definition,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    protected function bearer(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken,
        ];
    }
}
