<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventDate = now()->addDays(fake()->numberBetween(3, 60))->startOfDay();

        return [
            'category_id' => Category::factory(),
            'organizer_id' => User::factory()->organizer(),
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->paragraph(),
            'venue' => fake()->randomElement([
                'Main Hall',
                'Sports Complex',
                'Library Lawn',
                'ICT Laboratory',
                'Chapel',
            ]),
            'event_date' => $eventDate->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'maximum_capacity' => fake()->numberBetween(20, 200),
            'registration_deadline' => $eventDate->copy()->subDay()->setTime(17, 0),
            'status' => EventStatus::Published,
        ];
    }
}
