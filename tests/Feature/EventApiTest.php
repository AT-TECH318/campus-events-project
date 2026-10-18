<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    private Category $category;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->category = Category::factory()->create([
            'name' => 'Career',
            'slug' => 'career',
        ]);
        $this->organizer = User::factory()->organizer()->create([
            'name' => 'Mwansa Zulu',
        ]);
    }

    public function test_guests_can_list_and_read_published_events(): void
    {
        $event = Event::factory()->create([
            'title' => 'Ndola Career Fair',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
        ]);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Ndola Career Fair')
            ->assertJsonPath('data.0.organizer.name', 'Mwansa Zulu')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->getJson('/api/v1/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('data.banner', null)
            ->assertJsonPath('data.status', 'published');
    }

    public function test_an_organizer_can_create_replace_update_and_delete_an_event(): void
    {
        $created = $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Career Fair')
            ->assertJsonPath('data.organizer.id', $this->organizer->id);

        $eventId = $created->json('data.id');

        $this->asOrganizer()
            ->putJson('/api/v1/events/'.$eventId, $this->payload([
                'title' => 'Career Fair Replaced',
                'venue' => 'Library Lawn',
            ]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Career Fair Replaced')
            ->assertJsonPath('data.venue', 'Library Lawn');

        $this->asOrganizer()
            ->patchJson('/api/v1/events/'.$eventId, [
                'title' => 'Career Fair Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Career Fair Updated')
            ->assertJsonPath('data.venue', 'Library Lawn');

        $this->asOrganizer()
            ->deleteJson('/api/v1/events/'.$eventId)
            ->assertNoContent();

        $this->getJson('/api/v1/events/'.$eventId)->assertNotFound();
    }

    public function test_students_and_guests_cannot_create_or_change_events(): void
    {
        $student = User::factory()->create();
        $event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
        ]);

        $this->postJson('/api/v1/events', $this->payload())->assertUnauthorized();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events', $this->payload())
            ->assertForbidden();

        $this->withHeaders($this->bearer($student))
            ->patchJson('/api/v1/events/'.$event->id, ['title' => 'Taken'])
            ->assertForbidden();

        $this->flushHeaders()
            ->deleteJson('/api/v1/events/'.$event->id)
            ->assertUnauthorized();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => $event->title,
        ]);
    }

    public function test_event_fields_dates_and_status_are_validated(): void
    {
        $this->asOrganizer()
            ->postJson('/api/v1/events', ['title' => 'Incomplete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'description',
                'category_id',
                'venue',
                'event_date',
                'start_time',
                'end_time',
                'maximum_capacity',
                'registration_deadline',
                'status',
            ]);

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'end_time' => '08:00:00',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'registration_deadline' => now()->addDays(11)->setTime(18, 0)->toDateTimeString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['registration_deadline']);

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'status' => 'postponed',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->asOrganizer()
            ->putJson('/api/v1/events/999999', $this->payload())
            ->assertNotFound();
    }

    public function test_events_can_be_searched_filtered_and_paged(): void
    {
        $sports = Category::factory()->create([
            'name' => 'Sports',
            'slug' => 'sports',
        ]);
        $otherOrganizer = User::factory()->organizer()->create([
            'name' => 'Tandiwe Phiri',
        ]);
        $matchDate = now()->addDays(4)->toDateString();

        Event::factory()->create([
            'title' => 'Ndola Career Fair',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
            'event_date' => $matchDate,
            'registration_deadline' => now()->addDays(2)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);
        Event::factory()->create([
            'title' => 'Chapel Choir',
            'category_id' => $sports->id,
            'organizer_id' => $otherOrganizer->id,
            'event_date' => now()->addDays(20)->toDateString(),
            'registration_deadline' => now()->addDays(18)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);
        Event::factory()->create([
            'title' => 'Already Held Lecture',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
            'event_date' => now()->subDays(3)->toDateString(),
            'registration_deadline' => now()->subDays(5)->setTime(17, 0),
            'status' => EventStatus::Published,
        ]);
        Event::factory()->create([
            'title' => 'Secret Draft',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
            'status' => EventStatus::Draft,
        ]);
        Event::factory()->create([
            'title' => 'Called Off Fair',
            'category_id' => $this->category->id,
            'organizer_id' => $this->organizer->id,
            'event_date' => now()->addDays(6)->toDateString(),
            'registration_deadline' => now()->addDays(4)->setTime(17, 0),
            'status' => EventStatus::Cancelled,
        ]);

        $this->getJson('/api/v1/events?title=Ndola')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Ndola Career Fair');

        $this->withToken('not-a-real-token')
            ->getJson('/api/v1/events')
            ->assertUnauthorized();

        $this->flushHeaders();

        $this->getJson('/api/v1/events?title='.urlencode('%'))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/events?organizer=Tandiwe')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Chapel Choir');

        $this->getJson('/api/v1/events?category=sports')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Chapel Choir');

        $this->getJson('/api/v1/events?category_id='.$this->category->id.'&date='.$matchDate)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Ndola Career Fair');

        $upcomingTitles = collect($this->getJson('/api/v1/events?timeframe=upcoming')->json('data'))
            ->pluck('title');
        $this->assertTrue($upcomingTitles->contains('Ndola Career Fair'));
        $this->assertTrue($upcomingTitles->contains('Chapel Choir'));
        $this->assertFalse($upcomingTitles->contains('Already Held Lecture'));
        $this->assertFalse($upcomingTitles->contains('Secret Draft'));
        $this->assertFalse($upcomingTitles->contains('Called Off Fair'));

        $this->getJson('/api/v1/events?timeframe=past')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Already Held Lecture');

        $this->getJson('/api/v1/events?title=Secret')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->asOrganizer()
            ->getJson('/api/v1/events?title=Secret')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/events?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/v1/events?timeframe=yesterday')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['timeframe']);

        $this->getJson('/api/v1/events?category_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_drafts_are_hidden_from_people_who_do_not_own_them(): void
    {
        $draft = Event::factory()->create([
            'title' => 'Secret Draft',
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'status' => EventStatus::Draft,
        ]);
        $student = User::factory()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        $admin = User::factory()->administrator()->create();

        $this->getJson('/api/v1/events/'.$draft->id)->assertForbidden();

        $this->withHeaders($this->bearer($student))
            ->getJson('/api/v1/events/'.$draft->id)
            ->assertForbidden();

        $this->withHeaders($this->bearer($otherOrganizer))
            ->getJson('/api/v1/events/'.$draft->id)
            ->assertForbidden();

        $this->asOrganizer()
            ->getJson('/api/v1/events/'.$draft->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->withHeaders($this->bearer($admin))
            ->getJson('/api/v1/events/'.$draft->id)
            ->assertOk();

        $this->getJson('/api/v1/events/999999')->assertNotFound();
    }

    public function test_banner_uploads_and_paths_stay_inside_public_event_storage(): void
    {
        Storage::fake('public');

        $banner = UploadedFile::fake()->createWithContent(
            'banner.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );

        $uploaded = $this->asOrganizer()->post('/api/v1/events', array_merge($this->payload(), [
            'banner' => $banner,
        ]));

        $uploaded->assertCreated()
            ->assertJsonPath('data.banner.original_name', 'banner.png');

        $path = $uploaded->json('data.banner.path');
        $this->assertIsString($path);
        $this->assertMatchesRegularExpression('#^events/banners/[A-Za-z0-9._-]+$#', $path);
        Storage::disk('public')->assertExists($path);

        Storage::disk('public')->put('events/banners/poster.jpg', 'poster');

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'title' => 'Poster Event',
                'banner_path' => 'events/banners/poster.jpg',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.banner.path', 'events/banners/poster.jpg');

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'banner_path' => '../.env',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['banner_path']);

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'banner_path' => 'events/banners/../../.env',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['banner_path']);

        $this->asOrganizer()
            ->postJson('/api/v1/events', $this->payload([
                'banner_path' => 'events/banners/missing.jpg',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['banner_path']);

        $this->asOrganizer()->post('/api/v1/events', array_merge($this->payload([
            'title' => 'Notes Event',
        ]), [
            'banner' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['banner']);
    }

    public function test_a_second_registration_for_the_same_user_conflicts(): void
    {
        $student = User::factory()->create();
        $event = Event::factory()->create([
            'maximum_capacity' => 5,
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
        ]);

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertStatus(409)
            ->assertJsonPath('reason', 'already_registered');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $date = now()->addDays(10)->startOfDay();

        return array_merge([
            'title' => 'Career Fair',
            'description' => 'Meet employers from Ndola.',
            'category_id' => $this->category->id,
            'venue' => 'Main Hall',
            'event_date' => $date->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'maximum_capacity' => 50,
            'registration_deadline' => $date->copy()->subDay()->setTime(17, 0)->toDateTimeString(),
            'status' => 'published',
        ], $overrides);
    }

    private function asOrganizer(): static
    {
        return $this->withHeaders(array_merge([
            'Accept' => 'application/json',
        ], $this->bearer($this->organizer)));
    }
}
