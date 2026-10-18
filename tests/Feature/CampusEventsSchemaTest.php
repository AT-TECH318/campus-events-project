<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Exceptions\EventRegistrationException;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusEventsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_creation_cannot_choose_a_privileged_role(): void
    {
        $administrator = Role::query()->firstOrCreate(
            ['slug' => Role::SLUG_ADMINISTRATOR],
            Role::definitions()[Role::SLUG_ADMINISTRATOR],
        );

        $user = User::query()->create([
            'name' => 'Public Student',
            'email' => 'public.student@campus.test',
            'password' => 'password',
            'role_id' => $administrator->id,
        ]);

        $this->assertTrue($user->hasRole(Role::SLUG_STUDENT));
        $this->assertNotSame($administrator->id, $user->role_id);
    }

    public function test_factory_can_assign_an_organizer_explicitly(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->assertTrue($organizer->hasRole(Role::SLUG_ORGANIZER));
    }

    public function test_event_relationships_and_date_scopes(): void
    {
        $upcoming = Event::factory()->create();
        $past = Event::factory()->create([
            'title' => 'Past guest lecture',
            'event_date' => now()->subDays(4)->toDateString(),
            'registration_deadline' => now()->subDays(6),
            'status' => EventStatus::Published,
        ]);
        Event::factory()->create([
            'title' => 'Draft seminar',
            'status' => EventStatus::Draft,
        ]);

        $this->assertTrue($upcoming->organizer->is($upcoming->organizer()->first()));
        $this->assertNotNull($upcoming->category);
        $this->assertTrue(Event::query()->upcoming()->whereKey($upcoming->id)->exists());
        $this->assertTrue(Event::query()->past()->whereKey($past->id)->exists());
        $this->assertFalse(Event::query()->upcoming()->where('title', 'Draft seminar')->exists());
        $this->assertFalse(Event::query()->past()->where('title', 'Draft seminar')->exists());
    }

    public function test_the_same_user_cannot_register_twice_for_one_event(): void
    {
        $event = Event::factory()->create(['maximum_capacity' => 5]);
        $user = User::factory()->create();

        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_registration_stops_at_capacity_and_rejects_a_duplicate_attempt(): void
    {
        $event = Event::factory()->create(['maximum_capacity' => 1]);
        $first = User::factory()->create();
        $second = User::factory()->create();

        $event->registerAttendee($first);

        try {
            $event->registerAttendee($second);
            $this->fail('A full event accepted another registration.');
        } catch (EventRegistrationException $exception) {
            $this->assertSame(EventRegistrationException::AT_CAPACITY, $exception->reason);
        }

        try {
            $event->registerAttendee($first);
            $this->fail('A duplicate registration was stored.');
        } catch (EventRegistrationException $exception) {
            $this->assertSame(EventRegistrationException::ALREADY_REGISTERED, $exception->reason);
        }

        $this->assertSame(1, $event->registrations()->count());
        $this->assertSame(0, $event->remainingCapacity());
    }

    public function test_closed_and_unpublished_events_reject_registration(): void
    {
        $closed = Event::factory()->create([
            'registration_deadline' => now()->subMinute(),
        ]);
        $draft = Event::factory()->create([
            'status' => EventStatus::Draft,
        ]);
        $student = User::factory()->create();

        try {
            $closed->registerAttendee($student);
            $this->fail('A closed event accepted a registration.');
        } catch (EventRegistrationException $exception) {
            $this->assertSame(EventRegistrationException::DEADLINE_PASSED, $exception->reason);
        }

        try {
            $draft->registerAttendee($student);
            $this->fail('A draft event accepted a registration.');
        } catch (EventRegistrationException $exception) {
            $this->assertSame(EventRegistrationException::NOT_OPEN, $exception->reason);
        }
    }

    public function test_category_delete_is_restricted_and_event_images_cascade(): void
    {
        $category = Category::factory()->create();
        $event = Event::factory()->create(['category_id' => $category->id]);
        $image = EventImage::factory()->create([
            'event_id' => $event->id,
            'is_banner' => true,
        ]);

        try {
            $category->delete();
            $this->fail('A category with events was deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('categories', ['id' => $category->id]);
        }

        $event->delete();

        $this->assertDatabaseMissing('event_images', ['id' => $image->id]);
    }

    public function test_a_new_banner_replaces_the_previous_banner(): void
    {
        $event = Event::factory()->create();
        $first = EventImage::factory()->create([
            'event_id' => $event->id,
            'is_banner' => true,
        ]);
        $second = EventImage::factory()->create([
            'event_id' => $event->id,
            'is_banner' => true,
        ]);

        $this->assertFalse($first->fresh()->is_banner);
        $this->assertTrue($second->fresh()->is_banner);
        $this->assertTrue($event->banner()->first()->is($second));
    }
}
