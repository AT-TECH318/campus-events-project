<?php

namespace Database\Factories;

use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiLog>
 */
class ApiLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'path' => '/api/v1/events',
            'status_code' => fake()->randomElement([200, 201, 204, 401, 403, 404, 422]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'duration_ms' => fake()->numberBetween(5, 400),
        ];
    }
}
