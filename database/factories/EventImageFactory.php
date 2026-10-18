<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'disk' => 'public',
            'path' => 'events/banners/'.fake()->uuid().'.jpg',
            'original_name' => 'banner.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(20000, 500000),
            'is_banner' => true,
        ];
    }
}
