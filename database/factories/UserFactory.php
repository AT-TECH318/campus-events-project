<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign the student role when the caller did not choose one.
     * role_id is not fillable, so this is set on the model directly.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            if ($user->role_id !== null) {
                return;
            }

            $user->role_id = $this->roleId(Role::SLUG_STUDENT);
        });
    }

    public function student(): static
    {
        return $this->withRole(Role::SLUG_STUDENT);
    }

    public function organizer(): static
    {
        return $this->withRole(Role::SLUG_ORGANIZER);
    }

    public function administrator(): static
    {
        return $this->withRole(Role::SLUG_ADMINISTRATOR);
    }

    public function withRole(string $slug): static
    {
        return $this->afterMaking(function (User $user) use ($slug): void {
            $user->role_id = $this->roleId($slug);
        });
    }

    private function roleId(string $slug): int
    {
        $definition = Role::definitions()[$slug];

        return Role::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition['name'],
                'description' => $definition['description'],
            ],
        )->getKey();
    }
}
