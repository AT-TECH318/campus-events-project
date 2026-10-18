<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Local demonstration password for every seeded account.
     * This is not a production credential.
     */
    public const DEMO_PASSWORD = 'password';

    /**
     * Seed demonstration users, events, registrations, images, and API log rows.
     * API log rows are sample records. They are not evidence that those requests were made.
     */
    public function run(): void
    {
        $student = $this->demoUser('Chipo Banda', 'student@campus.test', Role::SLUG_STUDENT);
        $organizer = $this->demoUser('Mwansa Zulu', 'organizer@campus.test', Role::SLUG_ORGANIZER);
        $this->demoUser('Tandiwe Phiri', 'admin@campus.test', Role::SLUG_ADMINISTRATOR);

        $categories = Category::query()->pluck('id', 'slug');

        $careerFair = $this->demoEvent([
            'category_id' => $categories['career'],
            'organizer_id' => $organizer->id,
            'title' => 'Ndola Career Fair',
            'description' => 'Employers from Ndola meet final-year students for internships and graduate roles.',
            'venue' => 'Main Hall',
            'event_date' => now()->addDays(21)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
            'maximum_capacity' => 120,
            'registration_deadline' => now()->addDays(18)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);

        $labTour = $this->demoEvent([
            'category_id' => $categories['academic'],
            'organizer_id' => $organizer->id,
            'title' => 'ICT Laboratory Tour',
            'description' => 'A small guided tour of the campus ICT laboratory for first-year students.',
            'venue' => 'ICT Laboratory',
            'event_date' => now()->addDays(7)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'maximum_capacity' => 1,
            'registration_deadline' => now()->addDays(5)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);

        $culturalNight = $this->demoEvent([
            'category_id' => $categories['cultural'],
            'organizer_id' => $organizer->id,
            'title' => 'Cultural Night',
            'description' => 'Student societies present music, dance, and poetry from across Zambia.',
            'venue' => 'Chapel',
            'event_date' => now()->subDays(14)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'maximum_capacity' => 200,
            'registration_deadline' => now()->subDays(16)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);

        $this->demoEvent([
            'category_id' => $categories['sports'],
            'organizer_id' => $organizer->id,
            'title' => 'Inter-faculty Football',
            'description' => 'Faculty teams play a short tournament on the campus sports ground.',
            'venue' => 'Sports Complex',
            'event_date' => now()->addDays(12)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '17:00:00',
            'maximum_capacity' => 80,
            'registration_deadline' => now()->addDays(10)->setTime(12, 0),
            'status' => EventStatus::Published,
        ]);

        $this->demoEvent([
            'category_id' => $categories['workshop'],
            'organizer_id' => $organizer->id,
            'title' => 'Research Methods Workshop',
            'description' => 'A draft workshop on citations, questionnaires, and basic data handling.',
            'venue' => 'Library Lawn',
            'event_date' => now()->addDays(30)->toDateString(),
            'start_time' => '09:30:00',
            'end_time' => '12:30:00',
            'maximum_capacity' => 40,
            'registration_deadline' => now()->addDays(27)->setTime(17, 0),
            'status' => EventStatus::Draft,
        ]);

        $this->demoEvent([
            'category_id' => $categories['social'],
            'organizer_id' => $organizer->id,
            'title' => 'Welcome Braai',
            'description' => 'A cancelled welcome social. The record is kept so past status changes remain visible.',
            'venue' => 'Library Lawn',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '19:00:00',
            'maximum_capacity' => 60,
            'registration_deadline' => now()->addDay()->setTime(17, 0),
            'status' => EventStatus::Cancelled,
        ]);

        $this->banner($careerFair, 'events/banners/ndola-career-fair.jpg', 'career-fair.jpg');
        $this->banner($culturalNight, 'events/banners/cultural-night.jpg', 'cultural-night.jpg');

        EventRegistration::query()->firstOrCreate(
            ['event_id' => $careerFair->id, 'user_id' => $student->id],
            ['registered_at' => now()->subDays(2)],
        );

        EventRegistration::query()->firstOrCreate(
            ['event_id' => $labTour->id, 'user_id' => $student->id],
            ['registered_at' => now()->subDay()],
        );

        $student->apiLogs()->updateOrCreate(
            ['method' => 'GET', 'path' => '/api/v1/events'],
            [
                'status_code' => 200,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'CampusEventsDemo/1.0',
                'duration_ms' => 42,
            ],
        );

        $student->apiLogs()->updateOrCreate(
            ['method' => 'POST', 'path' => '/api/v1/events/'.$labTour->id.'/registrations'],
            [
                'status_code' => 201,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'CampusEventsDemo/1.0',
                'duration_ms' => 67,
            ],
        );
    }

    private function demoUser(string $name, string $email, string $roleSlug): User
    {
        $roleId = Role::query()->where('slug', $roleSlug)->value('id');
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->role_id = $roleId;
            $existing->save();

            return $existing;
        }

        return User::factory()->withRole($roleSlug)->create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function demoEvent(array $attributes): Event
    {
        return Event::query()->updateOrCreate(
            ['title' => $attributes['title']],
            $attributes,
        );
    }

    private function banner(Event $event, string $path, string $originalName): void
    {
        EventImage::query()->updateOrCreate(
            ['event_id' => $event->id, 'path' => $path],
            [
                'disk' => 'public',
                'original_name' => $originalName,
                'mime_type' => 'image/jpeg',
                'size_bytes' => 180000,
                'is_banner' => true,
            ],
        );
    }
}
